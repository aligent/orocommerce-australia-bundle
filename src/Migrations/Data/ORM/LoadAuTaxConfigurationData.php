<?php

namespace Aligent\AustraliaBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;

class LoadAuTaxConfigurationData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var array */
    private static $configurations = [
        'origin_address' => [
            'country' => 'AU',
            'region' => 'AU-SA', #South Australia
            'region_text' => null,
            'postal_code' => '5000' #Adelaide
        ],
        'use_as_base_by_default' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
        'address_resolver_granularity' => 'country',
        'shipping_tax_code' => ['GST'],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Aligent\AustraliaBundle\Migrations\Data\ORM\LoadAuTaxTableRatesData'
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $productTaxCodeRepo = $this->container->get('oro_tax.repository.product_tax_code');
        $taxCodes = $productTaxCodeRepo->findBy(['code' => self::$configurations['shipping_tax_code']]);

        self::$configurations['shipping_tax_code'] = array_map(
            function (AbstractTaxCode $taxCode) {
                return $taxCode->getId();
            },
            $taxCodes
        );

        $configManager = $this->container->get('oro_config.global');

        foreach (self::$configurations as $option => $value) {
            if ($option == 'shipping_tax_code') {
                $foo='bar';
            }
            $configManager->set(
                OroTaxExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $option,
                $value
            );
        }

        $configManager->flush();
    }
}
