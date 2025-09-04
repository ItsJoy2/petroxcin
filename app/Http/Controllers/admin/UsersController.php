<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->filter;
         $search = $request->search;
        $page = $request->get('page', 1);
         $cacheKey = "users_{$filter}_{$search}_page_{$page}";

            $users = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filter, $search) {$query = User::query()->where('role', 'user')->withSum('investor', 'investment');

            switch ($filter) {
                case 'active':
                    $query->where('is_active', 1);
                    break;
                case 'inactive':
                    $query->where('is_active', 0);
                    break;
                case 'blocked':
                    $query->where('is_block', 1);
                    break;
                case 'unblocked':
                    $query->where('is_block', 0);
                    break;
            }
            if (!empty($search)) {
                $query->where('email', 'like', '%' . $search . '%');
            }

            return $query->paginate(10);
        });

        return view('admin.pages.users.index', compact('users'));
    }

    public function update(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->update($request->all());

        $this->clearUserCache();

        return redirect()->back()->with('success', 'User updated successfully');
    }

    private function clearUserCache()
    {
        $filters = ['active', 'inactive', 'blocked', 'unblocked', null];

        for ($page = 1; $page <= 10; $page++) {
            foreach ($filters as $filter) {
                $key = "users_{$filter}_page_{$page}";
                Cache::forget($key);
            }
        }
    }
}
