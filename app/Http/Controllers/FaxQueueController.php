<?php

namespace App\Http\Controllers;

use App\Models\FaxQueues;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class FaxQueueController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function index(Request $request)
    {
        // Check permissions
        if (!userCheckPermission("fax_queue_all")) {
            return redirect('/');
        }

        $statuses = ['all' => 'Show All', 'sent' => 'Sent', 'waiting' => 'Waiting', 'failed' => 'Failed', 'sending' => 'Sending'];
        $scopes = ['global', 'local'];
        $selectedStatus = $request->get('status');
        $searchString = $request->get('search');
        $selectedScope = $request->get('scope', 'local');
        $searchPeriod = $request->get('period');
        $period = [
            Carbon::now()->startOfDay()->subDays(30),
            Carbon::now()->endOfDay()
        ];

        if(preg_match('/^(0[1-9]|1[1-2])\/(0[1-9]|1[0-9]|2[0-9]|3[0-1])\/([1-9+]{2})\s(0[0-9]|1[0-2]:([0-5][0-9]?\d))\s(AM|PM)\s-\s(0[1-9]|1[1-2])\/(0[1-9]|1[0-9]|2[0-9]|3[0-1])\/([1-9+]{2})\s(0[0-9]|1[0-2]:([0-5][0-9]?\d))\s(AM|PM)$/', $searchPeriod)) {
            $e = explode("-", $searchPeriod);
            $period[0] = Carbon::createFromFormat('m/d/y h:i A', trim($e[0]));
            $period[1] = Carbon::createFromFormat('m/d/y h:i A', trim($e[1]));
        }

        // Get local Time Zone
        $timeZone = get_local_time_zone(Session::get('domain_uuid'));
        $domainUuid = Session::get('domain_uuid');
        $faxQueues = FaxQueues::query();
        if (in_array($selectedScope, $scopes) && $selectedScope == 'local') {
            $faxQueues
                ->where('domain_uuid', $domainUuid);
        } else {
            $faxQueues
                ->join('v_domains','v_domains.domain_uuid','=','v_fax_queue.domain_uuid');
        }
        if (array_key_exists($selectedStatus, $statuses) && $selectedStatus != 'all') {
            $faxQueues
                ->where('fax_status', $selectedStatus);
        }
        if ($searchString) {
            $faxQueues->where(function ($query) use ($searchString) {
                $query
                    ->orWhereLike('fax_email_address', strtolower($searchString));
                try {
                    $phoneNumberUtil = PhoneNumberUtil::getInstance();
                    $phoneNumberObject = $phoneNumberUtil->parse($searchString, 'US');
                    if ($phoneNumberUtil->isValidNumber($phoneNumberObject)) {
                        $query->orWhereLike('fax_caller_id_number', $phoneNumberUtil->format($phoneNumberObject, PhoneNumberFormat::E164));
                        $query->orWhereLike('fax_number', $phoneNumberUtil->format($phoneNumberObject, PhoneNumberFormat::E164));
                    } else {
                        $query->orWhereLike('fax_caller_id_number', str_replace("-", "",  $searchString));
                        $query->orWhereLike('fax_number', str_replace("-", "",  $searchString));
                    }
                } catch (NumberParseException $e) {
                    $query->orWhereLike('fax_caller_id_number', str_replace("-", "",  $searchString));
                    $query->orWhereLike('fax_number', str_replace("-", "",  $searchString));
                }
            });
        }
        $faxQueues->whereBetween('fax_date', $period);
        $faxQueues = $faxQueues->orderBy('fax_date', 'desc')->paginate(10)->onEachSide(1);

        foreach ($faxQueues as $i => $faxQueue) {
            $faxQueues[$i]['fax_date'] = Carbon::parse($faxQueue['fax_date'])->setTimezone($timeZone);
            if(!empty($faxQueue['fax_notify_date'])) {
                $faxQueues[$i]['fax_notify_date'] = Carbon::parse($faxQueue['fax_notify_date'])->setTimezone($timeZone);
            }
            if(!empty($faxQueue['fax_retry_date'])) {
                $faxQueues[$i]['fax_retry_date'] = Carbon::parse($faxQueue['fax_retry_date'])->setTimezone($timeZone);
            }
        }

        $data = array();
        $data['faxQueues'] = $faxQueues;
        $data['statuses'] = $statuses;
        $data['selectedStatus'] = $selectedStatus;
        $data['selectedScope'] = $selectedScope;
        $data['searchString'] = $searchString;
        $data['searchPeriodStart'] = $period[0]->format('m/d/y h:i A');
        $data['searchPeriodEnd'] = $period[1]->format('m/d/y h:i A');
        $data['searchPeriod'] = implode(" - ", [$data['searchPeriodStart'], $data['searchPeriodEnd']]);
        $data['national_phone_number_format'] = PhoneNumberFormat::NATIONAL;

        unset($statuses, $faxQueues, $faxQueue, $domainUuid, $timeZone, $selectedStatus, $searchString, $selectedScope);

        $permissions['delete'] = userCheckPermission('fax_queue_delete');
        $permissions['view'] = userCheckPermission('fax_queue_view');

        return view('layouts.faxqueue.list')
            ->with($data)
            ->with('permissions', $permissions);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function destroy($id)
    {
        $faxQueue = FaxQueues::findOrFail($id);

        if (isset($faxQueue)) {
            $deleted = $faxQueue->delete();
            if ($deleted) {
                return response()->json([
                    'status' => 200,
                    'success' => [
                        'message' => 'Selected entries have been deleted'
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'error' => [
                        'message' => 'There was an error deleting selected entries'
                    ]
                ]);
            }
        }
    }

    public function updateStatus(FaxQueues $faxQueue, $status = null)
    {
        $faxQueue->update([
            'fax_status' => $status,
            'fax_retry_count' => 0,
            'fax_retry_date' => null
        ]);

        return redirect()->back();
    }
}
