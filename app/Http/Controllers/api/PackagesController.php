<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\Package;
use App\Models\referrals_settings;
use App\Service\TransactionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackagesController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function getPackages():JsonResponse
    {
        $packages = Package::where('active', 1)->get();
        return response()->json([
            'status' => true,
            'data' => $packages,
        ]);
    }


    public function BuyPackage(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'package_id' => 'required|exists:package,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $package = Package::find($validatedData['package_id']);
        $amount = $validatedData['amount'];
        $user = $request->user();
        $packageName = $package->name;

        if($user->is_block == 1){
            return response()->json([
                'status' => false,
                'message' => 'Sorry, you cannot make a transaction because it is blocked'
            ],401);
        }

        if ($amount < $package->min_amount || $amount > $package->max_amount) {
            return response()->json([
                'status' => false,
                'message' => 'Minimum or maximum amount not allowed',
            ]);
        }

        if ($user->wallet < $amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient funds',
            ]);
        }

        DB::beginTransaction();

        try {
            $user->wallet -= $amount;
            $user->is_active = 1;
            $user->save();
            $this->transactionService->addNewTransaction(
                $user->id,
                $amount,
                "package_purchased",
                "-",
                "$packageName Package Purchased"
            );

            Investor::create([
                'user_id' => $user->id,
                'package_name' => $packageName,
                'return_type' => $package->return_type,
                'package_id' => $validatedData['package_id'],
                'investment' => $amount,
                'duration' => $package->duration ?? null,
                'total_due_day' => $package->duration,
                'start_date' => now(),
                'next_cron' => now()->addDay(),
                'last_cron' => now(),
            ]);

            $invest_level_1 = referrals_settings::first()->invest_level_1 ?? 0;

            DB::commit();

            //level 1 bonus function here
            $level1 = $user->referredBy()->first();
            if($level1){
                $bonus = $amount * $invest_level_1 / 100;
                if ($level1->is_active){
                    $level1->increment('profit_wallet', $bonus);
                    $this->transactionService->addNewTransaction(
                        "$level1->id",
                        "$bonus",
                        "referral_commission",
                        "+",
                        "Level 1 Referral From $user->name"
                    );
                    $level1->save();
                }
            }
            Cache::forget('admin_dashboard_data');
            Cache::forget('packages_active_page_1');
            Cache::forget('packages_inactive_page_1');
            return response()->json([
                'status' => true,
                'message' => 'Package purchased successfully',
                'wallet_balance' => $user->wallet,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    public function InvestHistory(Request $request): JsonResponse{
        $user = $request->user();
        $investorData = Investor::where('user_id', $user->id)
            ->join('package', 'investors.package_id', '=', 'package.id')
            ->select('investors.*', 'package.interest_rate')
            ->paginate(10);
        $investorData->getCollection()->transform(function ($item) {
            $item->daily_roi = ($item->interest_rate * $item->investment) / 100;
            return $item;
        });
        return response()->json([
            'status' => true,
            'data' => $investorData->items(),
            'total' => $investorData->total(),
            'current_page' => $investorData->currentPage(),
            'last_page' => $investorData->lastPage(),

        ]);
    }

}
