<?php

namespace App\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Coderstm\Notifications\NewAdminNotification;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AdminController extends Controller
{
    use \Coderstm\Traits\HasResourceActions;

    /**
     * Create the controller instance.
     */
    public function __construct()
    {
        $this->useModel(Coderstm::$adminModel);
        $this->authorizeResource(Coderstm::$adminModel, 'admin', [
            'except' => ['show', 'update', 'destroy', 'restore']
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $admin = Coderstm::$adminModel::with('lastLogin', 'groups');

        if ($request->has('filter') && !empty($request->filter)) {
            $admin->where(DB::raw("CONCAT(first_name,' ',last_name)"), 'like', "%{$request->filter}%");
            $admin->orWhere('email', 'like', "%{$request->filter}%");
        }

        if ($request->has('group') && !empty($request->group)) {
            $admin->whereHas('groups', function ($query) use ($request) {
                $query->where('id', $request->group);
            });
        }

        if ($request->boolean('active')) {
            $admin->onlyActive();
        }

        if ($request->boolean('hideCurrent')) {
            $admin->excludeCurrent();
        }

        if ($request->boolean('deleted')) {
            $admin->onlyTrashed();
        }

        $admin = $admin->sortBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->rowsPerPage ?? 15);
        return new ResourceCollection($admin);
    }

    /**
     * Display a options listing of the resource.
     */
    public function options(Request $request)
    {
        $request->merge([
            'option' => true
        ]);

        return $this->index($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:6|confirmed',
        ];

        $request->validate($rules);

        $password = $request->filled('password') ? $request->password : fake()->regexify('/^IN@\d{3}[A-Z]{4}$/');

        $request->merge([
            'password' => bcrypt($password),
        ]);

        $admin = Coderstm::$adminModel::create($request->input());

        $admin->syncGroups(collect($request->groups));

        $admin->syncPermissions(collect($request->permissions));

        $admin->notify(new NewAdminNotification($admin, $password));

        return response()->json([
            'data' => $admin->load('groups', 'permissions'),
            'message' => __('Staff account has been created successfully!'),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($admin)
    {
        $admin = Coderstm::$adminModel::findOrFail($admin);

        $this->authorize('view', [$admin]);

        $admin = $admin->load([
            'permissions',
            'groups',
            'lastLogin',
        ]);
        return response()->json($this->toArray($admin), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $admin)
    {
        $admin = Coderstm::$adminModel::findOrFail($admin);

        $this->authorize('update', [$admin]);

        // Set rules
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'email|unique:admins,email,' . $admin->id,
            'password' => 'min:6|confirmed',
        ];

        // Validate those rules
        $request->validate($rules);

        if ($request->filled('password')) {
            $request->merge([
                'password' => bcrypt($request->password),
            ]);
        }

        if ($admin->id == user()->id) {
            $admin->update($request->except(['is_active', 'is_supper_admin']));
        } else {
            $admin->update($request->input());
        }

        $admin->syncGroups(collect($request->groups));

        $admin->syncPermissions(collect($request->permissions));

        return response()->json([
            'data' => $this->toArray($admin->load('groups', 'permissions')),
            'message' => __('Staff account has been updated successfully!'),
        ], 200);
    }

    /**
     * Display a listing of the permission.
     */
    public function modules(Request $request)
    {
        $modules = Module::with('permissions')->get()->map(function ($item) {
            $item->label = __($item->name);
            return $item;
        });

        return response()->json($modules, 200);
    }

    /**
     * Send reset password request to specified resource from storage.
     */
    public function resetPasswordRequest(Request $request, $id)
    {
        $admin = Coderstm::$adminModel::findOrFail($id);

        $status = Password::sendResetLink([
            'email' => $admin->email,
        ]);

        return response()->json([
            'status' => $status,
            'message' => __('Password reset link sent successfully!'),
        ], 200);
    }

    /**
     * Change admin of specified resource from storage.
     */
    public function changeAdmin(Request $request, $id)
    {
        $admin = Coderstm::$adminModel::findOrFail($id);

        $this->authorize('update', [$admin]);

        if ($admin->id == user()->id) {
            return response()->json([
                'message' => __('Staff can not update permissions of his/her self account.'),
            ], 403);
        }

        $admin->update([
            'is_supper_admin' => !$admin->is_supper_admin
        ]);

        $type = $admin->is_supper_admin ? 'marked' : 'unmarked';

        return response()->json([
            'message' => __('Staff account :type as admin successfully!', ['type' => __($type)]),
        ], 200);
    }

    /**
     * Change active of specified resource from storage.
     */
    public function changeActive(Request $request, $id)
    {
        $admin = Coderstm::$adminModel::findOrFail($id);

        $this->authorize('update', [$admin]);

        if ($admin->id == user()->id) {
            return response()->json([
                'message' => __('Reply has been created successfully!'),
            ], 403);
        }

        $admin->update([
            'is_active' => !$admin->is_active
        ]);

        $type = !$admin->is_active ? 'active' : 'deactive';

        return response()->json([
            'message' => __('Staff account marked as :type successfully!', ['type' => __($type)]),
        ], 200);
    }

    private function toArray($admin)
    {
        $data = $admin->toArray();

        $data['permissions'] = $admin->permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'access' => $permission->pivot->access,
            ];
        });

        $data['groupPermissions'] = $admin->getPermissionsViaGroups()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'access' => $permission->pivot->access,
            ];
        });

        return $data;
    }
}
