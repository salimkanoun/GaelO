<?php

namespace Tests\Feature;

use App\GaelO\UseCases\GetVisit\VisitEntity;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use App\Visit;
use App\VisitGroup;
use App\VisitType;
use App\Patient;

class VisitTest extends TestCase
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

    protected function setUp() : void {
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class)->create(['name' => 'test', 'patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => 'test']);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup['id']]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => 'test', 'center_code' => 0]);
        $this->validPayload = [
            'creatorUserId' => 1,
            'patientCode' => $this->patient['code'],
            'visitTypeId' => $this->visitType['id'],
            'statusDone' => 'Done',
        ];
    }

    public function testGetVisit(){
        $visit = factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => $this->patient['code'],
        'visit_type_id' => $this->visitType['id'],
        'status_done' => 'Done']);
        $response = $this->json('GET', 'api/visits/'.$visit['id'].'?role=investigator')->content();
        $response = json_decode($response, true);
        //Check all Item in visitEntity are present in reponse
        foreach ( get_class_vars(VisitEntity::class) as $key=>$value ){
            //Camelize keys
            $key = str_replace('_', '', lcfirst(ucwords($key, '_')));
            $this->assertArrayHasKey($key, $response);
        }
    }

    public function testGetVisits(){
        $visit = factory(Visit::class, 5)->create(['creator_user_id' => 1,
        'patient_code' => $this->patient['code'],
        'visit_type_id' => $this->visitType['id'],
        'status_done' => 'Done']);
        $this->json('GET', 'api/visits/?role=investigator')->assertJsonCount(5);
    }


    public function testCreateVisit() {

        $resp = $this->json('POST', 'api/studies/test/visit-groups/'.$this->visitGroup['id'].
        '/visit-types/'.$this->visitType['id'].'/visits'.'?role=investigator', $this->validPayload)->assertStatus(201);
        //Check record in database
        $visit = Visit::get()->first()->toArray();
        $this->assertNotEmpty($visit);
    }

    public function testGetPatientVisits() {
        $this->patient2 = factory(Patient::class)->create(['code' => 12341234123413, 'study_name' => 'test', 'center_code' => 0]);

        $visit = factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => $this->patient['code'],
        'visit_type_id' => $this->visitType['id'],
        'status_done' => 'Done']);

        $resp = $this->json('GET', 'api/visits/'.$visit['id'].'/patients/'.$this->patient['code'].'?role=investigator')->content();
        $resp = json_decode($resp, true);

        foreach ( get_class_vars(VisitEntity::class) as $key=>$value ){
            //Camelize keys
            $key = str_replace('_', '', lcfirst(ucwords($key, '_')));
            $this->assertArrayHasKey($key, $resp);
        }

        factory(Visit::class, 5)->create(['creator_user_id' => 1,
        'patient_code' => $this->patient['code'],
        'visit_type_id' => $this->visitType['id'],
        'status_done' => 'Done']);

        factory(Visit::class, 5)->create(['creator_user_id' => 1,
        'patient_code' => $this->patient2['code'],
        'visit_type_id' => $this->visitType['id'],
        'status_done' => 'Done']);

        $resp = $this->json('GET', 'api/visits/0/patients/'.$this->patient['code'].'?role=investigator')->assertJsonCount(6);
    }
}