<?php
namespace tests\src\PyAngelo\Controllers\Lessons;

use PHPUnit\Framework\TestCase;
use Mockery;
use Framework\Request;
use Framework\Response;
use PyAngelo\Controllers\Lessons\LessonsEditController;

class LessonsEditControllerTest extends TestCase {
  public function setUp(): void {
    $this->request = new Request($GLOBALS);
    $this->response = new Response('views');
    $this->auth = Mockery::mock('PyAngelo\Auth\Auth');
    $this->tutorialRepository = Mockery::mock('PyAngelo\Repositories\TutorialRepository');
    $this->controller = new LessonsEditController (
      $this->request,
      $this->response,
      $this->auth,
      $this->tutorialRepository
    );
  }
  public function tearDown(): void {
    Mockery::close();
  }

  public function testClassCanBeInstantiated() {
    $this->assertSame(get_class($this->controller), 'PyAngelo\Controllers\Lessons\LessonsEditController');
  }

  public function testWhenNotAdmin() {
    $this->auth->shouldReceive('isAdmin')->once()->with()->andReturn(false);

    $response = $this->controller->exec();
    $responseVars = $response->getVars();
    $expectedHeaders = array(array('header', 'Location: /'));
    $expectedFlashMessage = "You are not authorised!";
    $this->assertSame($expectedHeaders, $response->getHeaders());
    $this->assertSame($expectedFlashMessage, $this->request->session['flash']['message']);
  }

  /**
   * @runInSeparateProcess
   */
  public function testWhenAdminSuccess() {
    session_start();
    $securityLevels = [
      [
        'lesson_security_level_id' => 1,
        'description' => 'Free members'
      ],
      [
        'lesson_security_level_id' => 2,
        'description' => 'Premium members'
      ],
    ];
    $tutorialTitle = 'The Best Tutorial';
    $tutorialSlug = 'the-best-tutorial';
    $lessonId = 99;
    $lessonTitle = 'The Best Lesson';
    $lessonSlug = 'the-best-lesson';
    $lesson = [
      'lesson_id' => $lessonId,
      'lesson_title' => $lessonTitle,
      'lesson_slug' => $lessonSlug
    ];
    $this->auth->shouldReceive('isAdmin')->once()->with()->andReturn(true);
    $this->auth->shouldReceive('getPersonDetailsForViews')->once()->with();
    $this->tutorialRepository
      ->shouldReceive('getLessonBySlugs')
      ->once()
      ->with($tutorialSlug, $lessonSlug)
      ->andReturn($lesson);
    $this->tutorialRepository
      ->shouldReceive('getAllLessonSecurityLevels')
      ->once()
      ->with()
      ->andReturn($securityLevels);
    $this->request->get['slug'] = $tutorialSlug;
    $this->request->get['lesson_slug'] = $lessonSlug;

    $response = $this->controller->exec();
    $responseVars = $response->getVars();
    $expectedViewName = 'lessons/edit.html.php';
    $expectedPageTitle = 'Edit Lesson';
    $expectedMetaDescription = "Edit the $lessonTitle lesson.";
    $this->assertSame($expectedViewName, $response->getView());
    $this->assertSame($expectedPageTitle, $responseVars['pageTitle']);
    $this->assertSame($expectedMetaDescription, $responseVars['metaDescription']);
  }
}
?>
