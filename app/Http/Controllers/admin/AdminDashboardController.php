<?php

namespace App\Http\Controllers\admin;

use App\Models\User;
use App\Models\Investor;
use Illuminate\View\View;
use App\Models\Transactions;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $dashboardData = Cache::remember('admin_dashboard_data', now()->hour(1), function () {
            return [

                // user
                'totalUser' => User::where('role', 'user')->count(),
                'activeUser' => User::where('is_active', 1)->where('role', 'user')->count(),
                'blockUser' => User::where('is_block', 1)->where('role', 'user')->count(),
                'newUser' => User::where('created_at', '>=', now()->startOfDay()->addHours(5))->where('role', 'user')->count(),

                // deposit
                'totalDeposits' => Transactions::where('remark', 'deposit')->whereIn('status', ['Completed', 'Paid'])->sum('amount'),
                'rejectedDeposits' => Transactions::where('remark', 'deposit')->where('status', 'rejected')->sum('amount'),
                'pendingDeposits' => Transactions::where('remark', 'deposit')->where('status', 'pending')->sum('amount'),

                // withdrawal
                'totalWithdrawals' => Transactions::where('remark', 'withdrawal')->where('status', 'Completed')->sum('amount'),
                'pendingWithdrawals' => Transactions::where('remark', 'withdrawal')->where('status', 'pending')->sum('amount'),
                'pendingWithdrawalsCount' => Transactions::where('remark', 'withdrawal')->where('status', 'pending')->count(),
                'rejectedWithdrawals' => Transactions::where('remark', 'withdrawal')->where('status', 'rejected')->sum('amount'),
                'totalCharges' => Transactions::sum('charge'),

                // Investment
                'totalInvestmentAmount' =>Investor::sum('investment'),
                'runningInvestmentAmount' => Investor::where('status', 1)->sum('investment'),
                'canceledInvestmentAmount' => Investor::where('status', 0)->sum('investment'),
                'expiredInvestmentAmount' => Investor::where('total_due_day', 0)->sum('investment'),



            ];
        });

        return view('admin.dashboard', compact('dashboardData'));
    }
}
