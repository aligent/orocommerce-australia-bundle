<?php

// The categories of organizations and/or items below that are excluded from sales tax collection in California
// are selected for demo and testing purposes only. More examples can be found in
// Sales and Use Tax: Exemptions and Exclusions (Publication 61) at http://www.boe.ca.gov/pdf/pub61.pdf

$customerTaxCodes = [
    'EXEMPT' => [
        'description' => 'GST Exempt Customers',
    ],
    'TAXABLE' => [
        'description' => 'Taxable Customers',
        'customer_groups' => [
            'Non-Authenticated Visitors'
        ]
    ],
];

$productTaxCodes = [
    'GST' => [
        'description' => 'Taxable Items',
    ],
    'GST_FREE' => [
        'description' => 'GST Free (Non-Taxable) Items',
    ],
];

$taxes = [
    'AU_GST' => ['rate' => 0.10, 'description' => 'Australian GST'],
];

$taxJurisdictions = [
    'AUSTRALIA' => [
        'country' => 'AU',
        'state' => '',
        'zip_codes' => [],
        'description' => 'Australia',
    ],
];

$taxRules = [
    [
        'customer_tax_code' => 'TAXABLE',
        'product_tax_code' => 'GST',
        'tax_jurisdiction' => 'AUSTRALIA',
        'tax' => 'AU_GST'
    ],
];

return [
    'customer_tax_codes' => $customerTaxCodes,
    'product_tax_codes' => $productTaxCodes,
    'taxes' => $taxes,
    'tax_jurisdictions' => $taxJurisdictions,
    'tax_rules' => $taxRules,
];
