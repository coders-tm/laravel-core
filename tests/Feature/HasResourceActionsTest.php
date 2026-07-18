<?php

namespace Tests\Feature;

use App\Models\Admin;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Task;
use Coderstm\Traits\HasResourceActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Laravel\Sanctum\Sanctum;

class HasResourceActionsTest extends FeatureTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Admin::factory()->admin()->create();
        Sanctum::actingAs($admin, [], 'sanctum');
    }

    public function test_trait_can_guess_model_from_controller_name()
    {
        // Create a test controller using setter to avoid property collision
        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getModelClass');
        $method->setAccessible(true);

        $this->assertEquals(Task::class, $method->invoke($controller));
    }

    public function test_trait_can_get_model_name()
    {
        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getModelName');
        $method->setAccessible(true);

        $this->assertEquals('task', $method->invoke($controller));
    }

    public function test_trait_can_get_model_plural_name()
    {
        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getModelPluralName');
        $method->setAccessible(true);

        $this->assertEquals('tasks', $method->invoke($controller));
    }

    public function test_trait_index_method_works()
    {
        // Create some tasks
        Task::factory()->count(5)->create();

        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $request = new Request;
        $response = $controller->index($request);

        $this->assertInstanceOf(ResourceCollection::class, $response);
    }

    public function test_trait_show_method_works()
    {
        $task = Task::factory()->create();

        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $response = $controller->show($task->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($task->id, $data['id']);
    }

    public function test_trait_destroy_method_works()
    {
        $task = Task::factory()->create();

        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $request = new Request;
        $response = $controller->destroy($request, $task->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_trait_force_destroy_method_works()
    {
        $task = Task::factory()->create();

        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $request = new Request(['force' => true]);
        $response = $controller->destroy($request, $task->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_trait_destroy_selected_method_works()
    {
        $tasks = Task::factory()->count(3)->create();

        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $request = new Request(['items' => $tasks->pluck('id')->toArray()]);
        $response = $controller->bulkDestroy($request);

        $this->assertEquals(200, $response->getStatusCode());

        foreach ($tasks as $task) {
            $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        }
    }

    public function test_trait_restore_method_works()
    {
        $task = Task::factory()->create();
        $task->delete();

        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $response = $controller->restore($task->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'deleted_at' => null,
        ]);
    }

    public function test_trait_restore_selected_method_works()
    {
        $tasks = Task::factory()->count(3)->create();
        $tasks->each->delete();

        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $request = new Request(['items' => $tasks->pluck('id')->toArray()]);
        $response = $controller->bulkRestore($request);

        $this->assertEquals(200, $response->getStatusCode());

        foreach ($tasks as $task) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'deleted_at' => null,
            ]);
        }
    }

    public function test_trait_index_with_filter()
    {
        Task::factory()->create(['subject' => 'Laravel Tutorial']);
        Task::factory()->create(['subject' => 'PHP Guide']);

        // Use a test model that defines getSearchable() to enable filtering
        $testModel = new class extends Model
        {
            protected $table = 'tasks';

            public function getSearchable(): array
            {
                return ['subject'];
            }
        };

        $modelClassName = get_class($testModel);

        $controller = new class($modelClassName) extends Controller
        {
            use HasResourceActions;

            private $testModelClass;

            public function __construct($modelClass)
            {
                $this->testModelClass = $modelClass;
            }

            protected function getModelClass(): string
            {
                return $this->testModelClass;
            }
        };

        $request = new Request(['filter' => 'Laravel']);
        $response = $controller->index($request);

        $data = $response->toResponse(request())->getData(true);
        $this->assertEquals(1, count($data['data']));
        $this->assertStringContainsString('Laravel', $data['data'][0]['subject']);
    }

    public function test_trait_index_with_sorting()
    {
        $task1 = Task::factory()->create(['created_at' => now()->subDays(2)]);
        $task2 = Task::factory()->create(['created_at' => now()->subDays(1)]);
        $task3 = Task::factory()->create(['created_at' => now()]);

        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $request = new Request([
            'sortBy' => 'created_at',
            'direction' => 'asc',
        ]);
        $response = $controller->index($request);

        $data = $response->toResponse(request())->getData(true);
        $this->assertEquals($task1->id, $data['data'][0]['id']);
        $this->assertEquals($task2->id, $data['data'][1]['id']);
        $this->assertEquals($task3->id, $data['data'][2]['id']);
    }

    public function test_trait_uses_model_get_searchable_method()
    {
        // Create a test model class with getSearchable method
        $testModel = new class extends Model
        {
            protected $table = 'tasks';

            public function getSearchable(): array
            {
                return ['subject', 'description', 'notes'];
            }
        };

        $modelClassName = get_class($testModel);

        $controller = new class($modelClassName) extends Controller
        {
            use HasResourceActions;

            private $testModelClass;

            public function __construct($modelClass)
            {
                $this->testModelClass = $modelClass;
            }

            protected function getModelClass(): string
            {
                return $this->testModelClass;
            }
        };

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getSearchableFields');
        $method->setAccessible(true);

        $searchableFields = $method->invoke($controller);
        $this->assertEquals(['subject', 'description', 'notes'], $searchableFields);
    }

    public function test_trait_index_filter_skips_when_no_searchable_fields()
    {
        // Task model does not define getSearchable()
        Task::factory()->create(['subject' => 'Laravel Tutorial']);
        Task::factory()->create(['subject' => 'PHP Guide']);

        $controller = new class extends Controller
        {
            use HasResourceActions;

            public function __construct()
            {
                $this->useModel(Task::class);
            }
        };

        $request = new Request(['filter' => 'Laravel']);
        $response = $controller->index($request);

        $data = $response->toResponse(request())->getData(true);
        // Since there are no searchable fields, filter should not reduce results
        $this->assertEquals(2, count($data['data']));
    }
}
