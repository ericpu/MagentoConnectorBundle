<?php

namespace Pim\Bundle\MagentoConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Model\AbstractProductMedia;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Product media normalizer
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductMediaNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = NULL, array $context = [])
    {
        $attributeCode = $object->getValue()->getAttribute()->getCode();

        return [
            [
                $attributeCode              => $object->getFileName(),
                $attributeCode . '_content' => base64_encode(file_get_contents($object->getFilePath()))
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = NULL)
    {
        return $data instanceof AbstractProductMedia && ProductNormalizer::API_IMPORT_FORMAT === $format;
    }

}
