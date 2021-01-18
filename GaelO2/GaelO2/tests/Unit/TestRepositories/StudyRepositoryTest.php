<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\StudyRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Study;

class StudyRepositoryTest extends TestCase
{
    private StudyRepository $studyRepository;

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->studyRepository = new StudyRepository(new Study());
    }

    public function testCreateStudy(){
        $this->studyRepository->addStudy('myStudy', '12345');
        $studyEntity  = Study::find('myStudy');

        $this->assertEquals('myStudy', $studyEntity->name);
        $this->assertEquals('12345', $studyEntity->patient_code_prefix);
    }

    public function testIsExistingStudy(){
        $studyEntity = Study::factory()->create();
        $answer  = $this->studyRepository->isExistingStudy($studyEntity->name);
        $answer2  = $this->studyRepository->isExistingStudy('NotExistingStudyName');
        $this->assertTrue($answer);
        $this->assertFalse($answer2);
    }

    public function testGetStudies(){

        Study::factory()->create();
        Study::factory()->create()->delete();

        $answer = $this->studyRepository->getStudies();
        $answer2 = $this->studyRepository->getStudies(true);

        $this->assertEquals(1, sizeof($answer) );
        $this->assertEquals(2, sizeof($answer2) );

    }

    public function testGetAllStudiesWithDetails(){

        Study::factory()->count(5)->create();
        Study::factory()->create()->delete();

        $answer = $this->studyRepository->getAllStudiesWithDetails();

        $this->assertEquals(6, sizeof($answer));
        $this->assertArrayHasKey('visit_group_details', $answer[0]);

    }

    public function testGetStudyDetails(){

        $study = Study::factory()->create();
        $answer = $this->studyRepository->getStudyDetails($study->name);
        $this->assertArrayHasKey('visit_group_details', $answer);
        $this->assertEquals($study->name, $answer['name']);
    }

    public function testReactivateStudy(){

        $study = Study::factory()->create();
        $study->delete();

        $this->studyRepository->reactivateStudy($study->name);

        $updatedStudy = Study::find($study->name);
        $this->assertNull($updatedStudy['deleted_at']);
    }

}