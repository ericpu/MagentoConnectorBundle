<?php

namespace Pim\Bundle\MagentoConnectorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to register tagged normalizers into the magento connector serializer
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @see       Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\SerializerPass
 */
class SerializerPass implements CompilerPassInterface
{
    /** @var string  */
    protected $serializerServiceId;

    /** @staticvar integer The default priority for services */
    const DEFAULT_PRIORITY = 100;

    /**
     * @param string $serializerServiceId
     */
    public function __construct($serializerServiceId)
    {
        $this->serializerServiceId  = $serializerServiceId;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->serializerServiceId)) {
            throw new \LogicException(
                sprintf('Resolver "%s" is called on an incorrect serializer service id', get_class($this))
            );
        }

        // Looks for all the services tagged "serializer.normalizer" and adds them to the Serializer service
        $normalizerTag = sprintf("%s.normalizer", $this->serializerServiceId);
        $normalizers = $this->findAndSortTaggedServices($normalizerTag, $container);

        $container->getDefinition($this->serializerServiceId)->setArguments([$normalizers]);
    }

    /**
     * Returns an array of service references for a specified tag name
     *
     * @param string           $tagName
     * @param ContainerBuilder $container
     *
     * @return \Symfony\Component\DependencyInjection\Reference[]
     */
    protected function findAndSortTaggedServices($tagName, ContainerBuilder $container)
    {
        $services = $container->findTaggedServiceIds($tagName);

        if (empty($services)) {
            throw new \RuntimeException(
                sprintf('You must tag at least one service as "%s" to use the Serializer service', $tagName)
            );
        }

        $sortedServices = array();
        foreach ($services as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $priority = isset($tag['priority']) ? $tag['priority'] : self::DEFAULT_PRIORITY;
                $sortedServices[$priority][] = new Reference($serviceId);
            }
        }

        krsort($sortedServices);

        // Flatten the array
        return call_user_func_array('array_merge', $sortedServices);
    }
}
