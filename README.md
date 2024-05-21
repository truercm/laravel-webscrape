![](https://banners.beyondco.de/Webscrape.png?theme=light&packageManager=composer+require&packageName=truercm%2Flaravel-webscrape&pattern=diagonalLines&style=style_1&description=Scrape+web+paged+within+Laravel+application&md=1&showWatermark=0&fontSize=100px&images=cloud-download)

# Webscrape 

Scrape web paged within Laravel application

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

Register package within `repositories` key of you `composer.json`:

```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/truercm/laravel-webscrape"
        }
    ],
```

You can install the package via composer:

```bash
composer require truercm/laravel-webscrape
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-webscrape-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-webscrape-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-webscrape-views"
```

## Usage

1. Initialize subject with credentials and target urls

```php

DB::transaction(function () {

    TrueRcm\LaravelWebscrape\Models\CrawlSubject::factory()
        ->for(TrueRcm\LaravelWebscrape\Models\CrawlTarget::factory()
            ->has(TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl::factory()->sequence(
                    [
                        'url_template' => 'https://proview.caqh.org/PR/PersonalInfo',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParsePersonalInfoPage::class,
                        'result_fields' => json_encode([
                            'name',
                            'aliases',
                            'gender',
                            'addresses',
                            'languages',
                            'birth_date',
                            'birth_country_id',
                            'birth_state',
                            'birth_city',
                            'npis',
                            'ssns',
                            'emails',
                            'citizenship_id',
                            'demographic'
                        ])
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/ProfessionalID',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseProfessionalIdPage::class,
                        'result_fields' => json_encode([
                            'medicaid',
                            'medicare',
                            'cdc',
                            'licenses'
                        ])
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/Education/EducationAndProfessionalTraining',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseEducationAndProfessionalTrainingPage::class,
                        'result_fields' => json_encode([
                            'education',
                            'trainings'
                        ])
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/Specialities',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseSpecialitiesPage::class,
                        'result_fields' => json_encode([
                            'specialties',
                            'areas_of_expertise',
                            'treatment_options',
                            'patient_populations',
                            'physical_conditions'
                        ])
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/PracticeLocation',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParsePracticeLocationPage::class,
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/HospitalAffiliation',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseHospitalAffiliationPage::class,
                        'result_fields' => json_encode([
                            'admitting_privileges',
                            'admitting_arrangements',
                            'non_admitting_affiliations'
                        ])
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/CredentialingContact',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseCredentialingContactPage::class,
                        'result_fields' => json_encode([
                            'credentialing_contacts'
                        ])
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/ProfessionalLiability',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseProfessionalLiabilityPage::class,
                        'result_fields' => json_encode([
                            'is_insured',
                            'is_ftca_covered'
                        ])
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/EmploymentInformation',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseEmploymentInformationPage::class,
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/References',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseReferencesPage::class,
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/Disclosure/MA',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseDisclosureMaPage::class,
                    ],
                    [
                        'url_template' => 'https://proview.caqh.org/PR/Documents',
                        'handler' => TrueRcm\LaravelWebscrape\Jobs\ParseDocumentsPage::class,
                        'result_fields' => json_encode([
                            'documents',
                        ])
                    ]
                    
                    
                )->count(12), 'crawlTargetUrls', 12)
            ->create([
                'auth_url' => 'https://proview.caqh.org/Login/Index',
                'crawling_job' => TrueRcm\LaravelWebscrape\Jobs\CrawlTargetJob::class,
            ])
        )
        ->create([
            'credentials' => json_encode([
                'UserName' => 'someuser',
                'Password' => 'mypassword'
            ]),
            'authenticated_at' => null,
        ]);
});
```

2. Start remote url crawling and processing the result

```php

    $crawlSubject = CrawlSubject::find(1);

    resolve($crawlSubject->crawlTarget->crawling_job)->dispatch($crawlSubject);

```

3. after job is finished we have final result in CrawlSubject's result column

```php

    $crawlSubject->fresh()->result;
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Purnendu Chandan](https://github.com/Purnendu-extreme)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
