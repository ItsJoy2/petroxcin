<?php

namespace App\Http\Controllers\admin;

use App\Models\User;
use App\Models\Investor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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

    public function investmentHistory(Request $request)
    {
        $query = Investor::with('user');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('from_date') && $request->has('to_date') && !empty($request->from_date) && !empty($request->to_date)) {
            $query->whereBetween('created_at', [
                $request->from_date . " 00:00:00",
                $request->to_date . " 23:59:59"
            ]);
        }

        $investors = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.pages.investment_history', compact('investors'));
    }
}
