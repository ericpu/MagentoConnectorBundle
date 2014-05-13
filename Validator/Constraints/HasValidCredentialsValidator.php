<?php

namespace Pim\Bundle\MagentoConnectorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\InvalidCredentialException;
use Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException;
use Pim\Bundle\MagentoConnectorBundle\Webservice\UrlExplorer;
use Pim\Bundle\MagentoConnectorBundle\Validator\Checks\XmlChecker;
use Pim\Bundle\MagentoConnectorBundle\Validator\Exception\NotReachableUrlException;
use Pim\Bundle\MagentoConnectorBundle\Validator\Exception\InvalidSoapUrlException;
use Pim\Bundle\MagentoConnectorBundle\Validator\Exception\InvalidXmlException;
use Pim\Bundle\MagentoConnectorBundle\Item\MagentoItemStep;

/**
 * Validator for Magento credentials
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class HasValidCredentialsValidator extends ConstraintValidator
{
    /**
     * @var WebserviceGuesser
     */
    protected $webserviceGuesser;

    /**
     * @var UrlExplorer
     */
    protected $urlExplorer;

    /**
     * @var XmlChecker
     */
    protected $xmlChecker;

    /**
     * @param WebserviceGuesser $webserviceGuesser
     * @param UrlExplorer      $urlExplorer
     * @param XmlChecker        $xmlChecker
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        UrlExplorer       $urlExplorer,
        XmlChecker        $xmlChecker
    ) {
        $this->webserviceGuesser = $webserviceGuesser;
        $this->urlExplorer       = $urlExplorer;
        $this->xmlChecker        = $xmlChecker;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param AbstractConfigurableStepElement $protocol   The value that should be validated
     * @param Constraint                      $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($protocol, Constraint $constraint)
    {
        if (!$protocol instanceof MagentoItemStep) {
            return null;
        }

        $clientParameters = new MagentoSoapClientParameters(
            $protocol->getSoapUsername(),
            $protocol->getSoapApiKey(),
            $protocol->getMagentoUrl(),
            $protocol->getWsdlUrl(),
            $protocol->getHttpLogin(),
            $protocol->getHttpPassword()
        );

        $objectId = spl_object_hash($clientParameters);

        if (!isset($this->valid[$objectId]) || false === $this->valid[$objectId]) {

            try {
                $xml = $this->soapExplorer->getSoapUrlContent($clientParameters);
                $this->xmlChecker->checkXml($xml);
                $this->webserviceGuesser->getWebservice($clientParameters);
            } catch (NotReachableUrlException $e) {
                $this->context->addViolationAt('wsdlUrl', $constraint->messageUrlNotReachable);
            } catch (InvalidSoapUrlException $e) {
                $this->context->addViolationAt('wsdlUrl', $constraint->messageSoapNotValid);
            } catch (InvalidXmlException $e) {
                $this->context->addViolationAt('wsdlUrl', $constraint->messageXmlNotValid);
            } catch (InvalidCredentialException $e) {
                $this->context->addViolationAt('soapUsername', $constraint->messageUsername);
            } catch (SoapCallException $e) {
                $this->context->addViolationAt('soapUsername', $e->getMessage());
            }

        }
    }

    /**
     * Are the given parameters valid ?
     *
     * @param MagentoSoapClientParameters $clientParameters
     *
     * @return boolean
     */
    public function areValidSoapCredentials(MagentoSoapClientParameters $clientParameters)
    {
        $objectId = spl_object_hash($clientParameters);

        if (!isset($this->valid[$objectId])) {

            try {
                $this->soapExplorer->getSoapUrlContent($clientParameters);
                $this->webserviceGuesser->getWebservice($clientParameters);
                $this->valid[$objectId] = true;
            } catch (NotReachableUrlException $e) {
                $this->valid[$objectId] = false;
            } catch (InvalidSoapUrlException $e) {
                $this->valid[$objectId] = false;
            } catch (InvalidCredentialException $e) {
                $this->valid[$objectId] = false;
            } catch (SoapCallException $e) {
                $this->valid[$objectId] = false;
            }
        }

        return $this->valid[$objectId];
    }
}
