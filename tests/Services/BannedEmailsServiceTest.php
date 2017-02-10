<?php

/**
 * Part of the Antares Project package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Ban Management
 * @version    0.9.0
 * @author     Antares Team
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017, Antares Project
 * @link       http://antaresproject.io
 */

namespace Antares\BanManagement\Services;

use Mockery as m;
use Antares\Testing\TestCase;
use Antares\BanManagement\Services\BannedEmailsService;
use Antares\BanManagement\Model\BannedEmail;
use Antares\BanManagement\Repositories\BannedEmailsRepository;
use Illuminate\Filesystem\Filesystem;
use Faker\Factory;
use CreateBanEmailsTables;

class BannedEmailsServiceTest extends TestCase
{

    /**
     * @var Mockery
     */
    protected $repository;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function setUp()
    {
        parent::setUp();

        $this->app->make(CreateBanEmailsTables::class)->up();

        $this->repository = $this->app->make(BannedEmailsRepository::class);
        $this->filesystem = $this->app->make(Filesystem::class);
    }

    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    /**
     * @return BannedEmailService
     */
    protected function getBannedEmailsService()
    {
        return new BannedEmailService($this->repository, $this->filesystem);
    }

    public function testSaveToFile()
    {
        $faker   = Factory::create();
        $service = $this->getBannedEmailsService();
        $service->saveToFile();

        $this->assertCount(0, $service->getEmailTemplates());
        $this->assertInternalType('array', $service->getEmailTemplates());

        foreach (range(0, 40) as $index) {
            BannedEmail::create([
                'email' => $faker->email,
            ]);
        }

        $service->saveToFile();

        $this->assertCount(BannedEmail::count(), $service->getEmailTemplates());
        $this->assertInternalType('array', $service->getEmailTemplates());
    }

    public function testGetEmails()
    {
        $faker   = Factory::create();
        $service = $this->getBannedEmailsService();

        foreach (range(0, 40) as $index) {
            BannedEmail::create([
                'email' => $faker->email,
            ]);
        }

        $service->saveToFile();
        $emails = $service->getEmailTemplates();

        foreach (BannedEmail::get() as $model) {
            $this->assertTrue(in_array($model->getEmailTemplates(), $emails));
        }
    }

    public function testIsEmailBanned()
    {
        $faker   = Factory::create();
        $service = $this->getBannedEmailsService();

        foreach (range(0, 40) as $index) {
            BannedEmail::create([
                'email' => $faker->email,
            ]);
        }

        $service->saveToFile();

        foreach (BannedEmail::get() as $model) {
            $this->assertTrue($service->isEmailBanned($model->getEmailTemplates()));
        }

        $this->assertFalse($service->isEmailBanned($faker->email));
    }

}