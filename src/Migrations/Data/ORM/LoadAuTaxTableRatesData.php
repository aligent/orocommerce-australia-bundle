<?php

namespace Aligent\AustraliaBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TaxBundle\Migrations\TaxEntitiesFactory;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;

class LoadAuTaxTableRatesData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * @var TaxEntitiesFactory
     */
    private $entitiesFactory;

    public function __construct()
    {
        $this->entitiesFactory = new TaxEntitiesFactory();
    }

    public function getDependencies()
    {
        return [
            LoadRolesData::class
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $data = require $locator->locate('@AligentAustraliaBundle/Migrations/Data/ORM/data/tax_table_rates.php');

        $this->loadCustomerTaxCodes($manager, $data['customer_tax_codes']);
        $this->loadProductTaxCodes($manager, $data['product_tax_codes']);
        $this->loadTaxes($manager, $data['taxes']);
        $this->loadTaxJurisdictions($manager, $data['tax_jurisdictions']);
        $this->loadTaxRules($manager, $data['tax_rules']);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param array $customerTaxCodes
     *
     * @return $this
     */
    private function loadCustomerTaxCodes(ObjectManager $manager, $customerTaxCodes)
    {
        $owner = $this->getAdminUser($manager);
        foreach ($customerTaxCodes as $code => $data) {
            $taxCode = $this->entitiesFactory->createCustomerTaxCode($code, $data['description'], $manager, $this);
            $taxCode->setOwner($owner);
            $taxCode->setOrganization($owner->getOrganization());
            if (isset($data['customer_groups'])) {
                foreach ($data['customer_groups'] as $groupName) {
                    $group = $manager->getRepository('OroCustomerBundle:CustomerGroup')->findOneByName($groupName);
                    if (null !== $group) {
                        $group->setTaxCode($taxCode);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $productTaxCodes
     *
     * @return $this
     */
    private function loadProductTaxCodes(ObjectManager $manager, $productTaxCodes)
    {
        $org = $this->getAdminUser($manager)->getOrganization();
        foreach ($productTaxCodes as $code => $data) {
            $taxCode = $this->entitiesFactory->createProductTaxCode($code, $data['description'], $org, $manager, $this);
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $taxes
     *
     * @return $this
     */
    private function loadTaxes(ObjectManager $manager, $taxes)
    {
        foreach ($taxes as $code => $data) {
            $this->entitiesFactory->createTax($code, $data['rate'], $data['description'], $manager, $this);
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $taxJurisdictions
     *
     * @return $this
     */
    private function loadTaxJurisdictions(ObjectManager $manager, $taxJurisdictions)
    {
        foreach ($taxJurisdictions as $code => $data) {
            $country = $this->getCountryByIso2Code($manager, $data['country']);
            $region = $this->getRegionByCountryAndCode($manager, $country, $data['state']);

            $jurisdiction = new TaxJurisdiction();
            $jurisdiction->setCode($code);
            $jurisdiction->setDescription($data['description']);
            if ($country) {
                $jurisdiction->setCountry($country);
                if ($region) {
                    $jurisdiction->setRegion($region);
                }
            }

            $manager->persist($jurisdiction);
            $this->addReference($code, $jurisdiction);
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $taxRules
     *
     * @return $this
     */
    private function loadTaxRules(ObjectManager $manager, $taxRules)
    {
        foreach ($taxRules as $rule) {
            /** @var \Oro\Bundle\TaxBundle\Entity\CustomerTaxCode $customerTaxCode */
            $customerTaxCode = $this->getReference($rule['customer_tax_code']);

            /** @var \Oro\Bundle\TaxBundle\Entity\ProductTaxCode $productTaxCode */
            $productTaxCode = $this->getReference($rule['product_tax_code']);

            /** @var \Oro\Bundle\TaxBundle\Entity\TaxJurisdiction $taxJurisdiction */
            $taxJurisdiction = $this->getReference($rule['tax_jurisdiction']);

            /** @var \Oro\Bundle\TaxBundle\Entity\Tax $tax */
            $tax = $this->getReference($rule['tax']);

            $this->entitiesFactory->createTaxRule(
                $customerTaxCode,
                $productTaxCode,
                $taxJurisdiction,
                $tax,
                isset($rule['description']) ? $rule['description'] : '',
                $manager
            );
        }

        return $this;
    }

    //region Helper methods for the methods that the corresponding repositories do not have
    /**
     * @param ObjectManager $manager
     * @param string $iso2Code
     *
     * @return Country|null
     */
    private function getCountryByIso2Code(ObjectManager $manager, $iso2Code)
    {
        return $manager->getRepository('OroAddressBundle:Country')->findOneBy(['iso2Code' => $iso2Code]);
    }

    /**
     * @param ObjectManager $manager
     * @param Country $country
     * @param string $code
     *
     * @return Region|null
     */
    private function getRegionByCountryAndCode(ObjectManager $manager, Country $country, $code)
    {
        return $manager->getRepository('OroAddressBundle:Region')->findOneBy(['country' => $country, 'code' => $code]);
    }
    //endregion

    /**
     * @param ObjectManager $manager
     * @return User
     * @throws \InvalidArgumentException
     */
    private function getAdminUser(ObjectManager $manager)
    {
        $repository = $manager->getRepository(Role::class);
        $role       = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \InvalidArgumentException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \InvalidArgumentException(
                'Administrator user should exist to load tax codes demo data.'
            );
        }

        return $user;
    }
}
