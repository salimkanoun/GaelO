<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;

class StudyTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp() : void{
        parent::setUp();
        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateStudy()
    {
        $payload = [
            'studyName'=>'NewStudy',
            'patientCodePrefix'=>'1234'
        ];
        $this->post('/api/studies', $payload)->assertNoContent(201);
        $this->post('/api/studies', $payload)->assertNoContent(409);
        $studyEntity = Study::where('name', 'NewStudy')->get()->toArray();
        $this->assertEquals('NewStudy',$studyEntity[0]['name']);
        $this->assertEquals('1234',$studyEntity[0]['patient_code_prefix']);
    }

    public function testGetStudy(){

        $studies = $this->get('/api/studies')->content();
        dd($studies);

    }
}