<?php

namespace App\Http\Controllers;

use App\Models\SecUsers;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use \Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:sanctum', ['except' => ['signin', 'signup']]);
    }

    public function token()
    {
        return Auth::user()->createToken("MyApp")->plainTextToken;
    }
    public function signin(Request $request)
    {
        // dd($request->name,$request->email);
        DB::statement("OPEN SYMMETRIC KEY TS2021_skey DECRYPTION BY CERTIFICATE TS2021");
        $userpwd = DB::table('sec_users')
                        ->select(DB::raw('CONVERT(varchar, DecryptByKey(userpwd)) as userpwd, userstatusid'))
                        ->where('loginid', strtoupper($request->Username))
                        ->get();
        // dd($userpwd);
        // dd(count($userpwd));
        if(count($userpwd) == 0)
        {
            return response()->json([
                'status' => 401,
                'message' => 'Invalid username!'
            ]);
        }

        $psw = null;
        foreach ($userpwd as $key => $value) {
            // dd($value->userpwd);
            $psw = $value->userpwd;
            $status = $value->userstatusid;
        }

        if($status == 0)
        {
            return response()->json([
                'status' => 401,
                'message' => 'User Locked!'
            ]);
        }
        if($psw != null && $psw == $request->password)
        {
            // $authUser = Auth::user(); 
            // $token =  $authUser->createToken('MyAuthApp')->plainTextToken; 
            // dd($authUser);
            $user = SecUsers::where('loginid', $request->Username)->get();
            // $user = SecUsers::find($userid);
            // dd($user);
            // $user = json_encode($user);
            foreach($user as $us) {
                $user_id   = $us->userid;
                $full_name = $us->full_name;
                $email     = $us->email;
            }
            // session(['id' => $user_id]);
            // dd($user_id);
            // Session::put('id', $user_id);
            $user = SecUsers::find($user_id);
            $token = $user->createToken("MyApp")->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'User signed in!',
                'data' => [
                    'id' => $user_id,
                    'User' => $full_name,
                    'email' => $email,
                    'token' => $token
                ]
            ]);

        }else{
            return response()->json([
                'status' => 401,
                'message' => 'Invalid password!'
            ]);
        }
        
        // if(Auth::attempt(['loginid' => $request->Username, 'userpwd' => $request->password]))
        // { 
        //     $authUser = Auth::user(); 
        //     $token =  $authUser->createToken('MyAuthApp')->plainTextToken; 
   
        //     return response()->json([
        //         'status' => 200,
        //         'message' => 'User signed in',
        //         'data' => [
        //             'name' => $authUser->name,
        //             'token' => $token,
        //         ]
        //     ]);
        // } 
        // else
        // { 
        //     return response()->json([
        //         'status' => 401,
        //         'message' => 'Unauthorised!'
        //     ]);
        // } 
    }
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ]);
   
        if($validator->fails())
        {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }
        else
        {
            $input = $request->all();
            $input['password'] = bcrypt($input['password']);
            $user = User::create($input);
            $success['token'] =  $user->createToken('MyAuthApp')->plainTextToken;
            $success['name'] =  $user->name;
    
            return response()->json([
                'status' => 200,
                'data' => [
                    'data' => $success
                ],
                'message' => 'User created successfully.'
            ], 200);
        }
    }

    public function logout(Request $request)
    {
        // Session::flush();
        // $request->session()->flush();
        // Auth::logout();
        // $request->user()->token()->revoke();
        Session::flush();
        return response()->json([
            'status' => 200,
            'message' => 'Successfully logged out',
        ]);
    }

    public function UserPages (int $userid)
    {
        $pages = DB::table('sec_users_windows')
                    ->leftJoin('windows_details', 'sec_users_windows.w_id', '=', 'windows_details.w_id')
                    ->leftJoin('portal_menu_links', 'windows_details.w_name', '=', 'portal_menu_links.page_link')
                    ->select('windows_details.w_title', 'windows_details.w_title_ar','w_name', 'portal_menu_links.category', 'portal_menu_links.newtab', 'portal_menu_links.visible',
                    DB::raw('ROW_NUMBER() over(partition by category order by windows_details.w_id) as rn'))
                    ->where('userid' , '=', $userid)
                    ->where('moduleid' , '=' , 13)
                    ->where('visible' , '=', 1)
                    ->where('view_flag' , '=', 1)
                    ->orderBy('category' , 'asc')
                    ->orderBy('windows_details.w_id' , 'asc')
                    ->get();

        // dd($pages);
        if(count($pages) > 0)
        {
            return response()->json([
                'status' => 200,
                'message' => '',
                'count' => count($pages),
                'pages' => $pages
            ], 200);
            // return $pages;
        }
        else
        {
            return response()->json([
                'status' => 422,
                'message' => 'No Pages Found!',
                'pages' => count($pages)
            ], 422);
        }
        
    }
    
    public function UserBranches(int $userid)
    {
        $branches = DB::table('mst_branches')
                        ->leftJoin('sec_users_branches', 'sec_users_branches.branchid', '=', 'mst_branches.branchid')
                        ->leftJoin('sec_users', 'sec_users.userid', '=', 'sec_users_branches.userid')
                        ->select('mst_branches.branchid', 'mst_branches.branch_name')
                        ->where('sec_users_branches.userid' , '=', $userid)
                        ->orderBy('mst_branches.branchid' , 'asc')
                        ->get();
        // dd($branches);
        if(count($branches) > 0)
        {
            return response()->json([
                'status' => 200,
                'message' => '',
                'count' => count($branches),
                'branches' => $branches
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 422,
                'message' => 'No Branches Found!',
                'branches' => count($branches)
            ], 422);
        }
    }

    // ================================= PURCHASING =========================================
    public function POReport(Request $request, int $userid)
    {
        $statement = ('select * from
        (
          select PO_ID,PO_SYMBOL,PO_DATE,PO_DeliveryDate,case when PO_PAY_PERC > 0 then FIRST_PAY_DATE else PO_DATE end as PAYMENT_DATE,ACTUAL_DEL_DATE,SUPPLIER,PROJECT_NAME,CUSTOMER,BRANCH,PAY_TERM,CUR_NAME,PO_AMT,(DUES_PERC*PO_AMT)/100 as DUES_AMT,
          case when RECEIVE_ITEM_QYT = 0 then \'None\'
          when (RECEIVE_ITEM_QYT > 0 and RECEIVE_ITEM_QYT < PO_ITEMS_QTY) then \'Partial\'
          when (RECEIVE_ITEM_QYT >= PO_ITEMS_QTY) then \'Completed\' end as RECEIVING_STATUS,
          PO_PAID_AMT,
          case when (RECEIVE_ITEM_QYT = 0 and PO_PAID_AMT = 0 and isnull(((DUES_PERC*PO_AMT)/100),0) > 0) then \'Issued\'
          when (((RECEIVE_ITEM_QYT > 0) and (RECEIVE_ITEM_QYT < PO_ITEMS_QTY)) or (PO_PAID_AMT > 0 and RECEIVE_ITEM_QYT < PO_ITEMS_QTY) or (PO_PAID_AMT = 0 and RECEIVE_ITEM_QYT =0 and isnull(((DUES_PERC*PO_AMT)/100),0) = 0)) then \'Active\'
          when ((RECEIVE_ITEM_QYT >= PO_ITEMS_QTY) and PO_PAID_AMT < PO_AMT) then \'Completed\'
          when ((RECEIVE_ITEM_QYT >= PO_ITEMS_QTY) and PO_PAID_AMT >= PO_AMT) then \'Closed\'
          end as PO_STATUS,
          case when (DEL_ITEM_QYT = 0) then \'None\'
          when (DEL_ITEM_QYT > 0 and DEL_ITEM_QYT < PO_ITEMS_QTY) then \'Partial\'
          else \'Completed\' end as DELIVERY_STATUS,
          CUST_PO_DATE,ANNOUNCE_DATE,POS_FALG,PO_ITEMS_QTY,RECEIVE_ITEM_QYT,DEL_ITEM_QYT,SO,MGR_CODE
          from (
          select po.porderid as PO_ID, po.porder_symbol as PO_SYMBOL, convert(varchar,po.entry_date,23) as PO_DATE,
          convert(varchar,po.delivery_date,23) as PO_DeliveryDate,
          (select case when isnull(dp_date, \'9999-99-99\') < isnull(inv_date, \'9999-99-99\')
          then dp_date else inv_date end as first_pay from (
          select pro.porderid, (select min(convert(varchar, pp.entry_date, 23)) from prc_payments pp where pp.post_flag = 1
          and isnull(pp.porderid, 0) = pro.porderid) as dp_date,
          (select min(convert(varchar, pp.entry_date, 23)) from prc_payments pp, prc_payments_details ppd
          where pp.ppaymentid = ppd.ppaymentid
          and pp.post_flag = 1
          and ppd.src_transtypeid = (select transtypeid from mst_transactions_types where transtype_symbol = \'PI\')
          and isnull((select isnull(pi.porderid, 0) from prc_invoices pi where pi.pinvoiceid = ppd.src_refid), 0) = pro.porderid
          and isnull(pp.porderid, 0) = 0) as inv_date from prc_purchase_orders pro
          where pro.porderid = po.porderid) mytable) as FIRST_PAY_DATE,
          (select convert(varchar,max(rn.entry_date),23) from prc_receipt_notes rn where rn.porderid=po.porderid group by rn.porderid) as ACTUAL_DEL_DATE,
          (select vendor_name from prc_vendors where vendorid=po.vendorid) as SUPPLIER,
          (select project_symbol from ppm_projects where sorderid=po.sorderid) as PROJECT_NAME,
          (select customer_name from sls_customers where customerid in(select customerid from sls_sales_orders where sorderid=po.sorderid)) as CUSTOMER,
          b.branch_name as BRANCH, pt.payterm_name as PAY_TERM, cu.currency_name as CUR_NAME, po.net_amt as PO_AMT,
          (select (select sum(pt.pay_perc)
          from mst_payment_terms_details pt
          where pt.paytermid = pod.paytermid
          and (
          (pt.transtypeid = (select tt.transtypeid from mst_transactions_types tt where tt.transtype_symbol = \'PO\')
          and (datediff(day, pod.entry_date , getdate())) >= pt.nof_days)
          or (pt.transtypeid = (select tt.transtypeid from mst_transactions_types tt where tt.transtype_symbol = \'RN\')
          and datediff(day,(select min(pi.entry_date) from prc_invoices pi where pi.porderid = pod.porderid), getdate()) >= pt.nof_days)
          or (pt.transtypeid = (select tt.transtypeid from mst_transactions_types tt where tt.transtype_symbol = \'RN\')
          and datediff(day,(select min(rn.entry_date) from prc_receipt_notes rn where rn.porderid = pod.porderid),getdate()) >= pt.nof_days)
          or (pt.transtypeid = (select tt.transtypeid from mst_transactions_types tt where tt.transtype_symbol = \'RN\')
          and isnull((select count(*) from inv_items it where it.icitemid in (select podd.icitemid from prc_purchase_orders_details podd where podd.porderid = pod.porderid)
          and it.stk_flag = 1), 0) = 0 and datediff(day, (select min(pi.entry_date) from prc_invoices pi where pi.porderid = pod.porderid), getdate()) >= pt.nof_days)
          )) from prc_purchase_orders pod where pod.porderid = po.porderid) as DUES_PERC,
          po.pp_amt as PO_PAID_AMT,
          convert(varchar,so.entry_date,23) as CUST_PO_DATE,
          convert(varchar,so.activation_date,23) as ANNOUNCE_DATE,po.post_flag as POS_FALG,
          (select sum(pd.qty) from prc_purchase_orders_details pd where pd.porderid=po.porderid) as PO_ITEMS_QTY,
          (select sum(case when pd.rn_qty > pd.pi_qty then pd.rn_qty else pd.pi_qty end) from prc_purchase_orders_details pd where pd.porderid=po.porderid) as RECEIVE_ITEM_QYT,
          isnull((select sum(sd.qty) from sls_delivery_notes_details sd
          where sd.sreceiptid in (select n.sreceiptid from sls_delivery_notes n
          where n.sorderid=po.sorderid) and sd.icitemid in (select prd.icitemid from prc_purchase_orders_details prd where prd.porderid=po.porderid)),0) as DEL_ITEM_QYT,po.sorderid as SO,ISNULL((select sum(pay_perc) from mst_payment_terms_details where transtypeid=(select transtypeid from mst_transactions_types where transtype_symbol=\'PO\') and paytermid=(select pu.paytermid from prc_purchase_orders pu where pu.porderid=po.porderid)),0) as PO_PAY_PERC,pm.empid as MGR_CODE
          from prc_purchase_orders po
          LEFT OUTER JOIN sls_sales_orders so ON po.sorderid=so.sorderid
          LEFT OUTER JOIN prc_invoices pin ON pin.porderid=po.porderid
          LEFT OUTER JOIN mst_currencies cu ON po.currencyid=cu.currencyid
          LEFT OUTER JOIN mst_payment_terms pt ON po.paytermid=pt.paytermid
          LEFT OUTER JOIN ppm_projects pp ON po.projectid=pp.projectid
            LEFT OUTER JOIN ppm_projects_managers pm ON pm.projectmanagerid=pp.projectmanagerid
          LEFT OUTER JOIN mst_branches b ON po.branchid=b.branchid
          ) mytable
        ) mytable
        where (mytable.POS_FALG=1 and (mytable.BRANCH IN (select b.branch_name from mst_branches b,sec_users_branches Ub,sec_users U where b.branchid=Ub.branchid and ub.userid=U.userid and u.userid='.$userid.')
        or ('.$userid.' IN (SELECT SC.USERID FROM sec_users_transactions_states SC WHERE SC.transtypeid=(SELECT transtypeid FROM mst_transactions_types WHERE transtype_symbol =\'POR\') and SC.stateid=100))))');

        if($request->date_from != null)
        {
            $statement .= " and mytable.PO_DATE >= CONVERT(varchar,'".$request->date_from."', 23)";
        }
        if($request->date_to != null)
        {
            $statement .= " and mytable.PO_DATE <= CONVERT(varchar,'".$request->date_to."', 23)";
        }
        if($request->branch != null)
        {
            $statement .= " and mytable.BRANCH='".$request->branch."'";
        }
        if($request->project != null)
        {
            $statement .= " and upper(PROJECT_NAME) LIKE upper('%{$request->project}%')";
        }
        if($request->po_code != null)
        {
            $statement .= " and upper(mytable.PO_SYMBOL) like upper('%{$request->po_code}%')";
        }
        if($request->po_status != null && $request->po_status != "Non-Closed")
        {
            $statement .= " and mytable.PO_STATUS='".$request->po_status."'";
        }
        if($request->po_status != null && $request->po_status == "Non-Closed")
        {
            $statement .= " and mytable.PO_STATUS != 'Closed'";
        }
        if($request->rec_status != null)
        {
            $statement .= " and mytable.RECEIVING_STATUS='".$request->rec_status."'";
        }
        if($request->dues != null)
        {
            $statement .=" and (DUES_AMT > PO_PAID_AMT and (DUES_AMT - PO_PAID_AMT) > 5)";
        }
        

        $statement .= ' group by mytable.PO_ID,mytable.PO_SYMBOL,mytable.PO_DATE,mytable.PO_DeliveryDate,mytable.PAYMENT_DATE,mytable.ACTUAL_DEL_DATE,
                        mytable.SUPPLIER,mytable.PROJECT_NAME,mytable.CUSTOMER,mytable.BRANCH,mytable.PAY_TERM,mytable.CUR_NAME,mytable.PO_AMT,
                        mytable.DUES_AMT,mytable.RECEIVING_STATUS,mytable.PO_PAID_AMT,PO_STATUS,mytable.DELIVERY_STATUS,mytable.CUST_PO_DATE,
                        mytable.ANNOUNCE_DATE,mytable.POS_FALG,mytable.PO_ITEMS_QTY,mytable.RECEIVE_ITEM_QYT,mytable.DEL_ITEM_QYT,mytable.SO,mytable.MGR_CODE
                        order by mytable.PO_SYMBOL';
        $po_report = DB::select($statement);
        
        // dd($po_report);
        if(count($po_report) > 0)
        {
            return response()->json([
                'status' => 200,
                'message' => '',
                'count' => count($po_report),
                'PO_Data' => $po_report
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 422,
                'message' => 'No PO Found!',
                'PO_Data' => count($po_report)
            ], 422);
        }
        
    }
    public function PODetails(int $po)
    {
        $statement="select porder_symbol, v.vendor_name from prc_purchase_orders po, prc_vendors v
                    where po.porderid=".$po." and po.vendorid=v.vendorid";
        
        $po_datails = DB::select($statement);
        
        // dd($po_datails);
        if(count($po_datails) > 0)
        {
            return response()->json([
                'status' => 200,
                'message' => 'Data was found',
                'count' => count($po_datails),
                'PO_Details' => $po_datails
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 422,
                'message' => 'No PO Found!',
                'PO_Details' => count($po_datails)
            ], 422);
        }
                    
    }
    public function POItems(int $po)
    {
        $statement="select porderid, icitem_symbol as PARTNO, icitem_name as ITEM_DESC, pd.sorderlineid as SO_ITEM ,convert(varchar,pd.delivery_date,23) as DEL_DATE,
        u.ui_name as UNIT, pd.qty as QTY, pd.rn_qty as RECEIVED_QTY, pd.pi_qty as INVOICE_QTY,
        pd.unit_price as UNIT_PRICE,pd.disc_perc as DISCOUNT, pd.disc_amt as DISCOUNT_VALUE,
        pd.net_price as NET_PRICE, (pd.qty*pd.unit_price) as TOT_PRICE
        from prc_purchase_orders_details pd
        LEFT OUTER JOIN inv_units_of_issue u ON pd.ui_id=u.ui_id
        where pd.porderid=".$po."";
        
        $po_items = DB::select($statement);
        
        // dd($po_datails);
        if(count($po_items) > 0)
        {
            return response()->json([
                'status' => 200,
                'message' => 'Data was found',
                'count' => count($po_items),
                'PO_Items' => $po_items
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 422,
                'message' => 'No PO Found!',
                'PO_Items' => count($po_items)
            ], 422);
        }
    }
    public function POReceived(int $po)
    {
        $statement= "select rn.preceiptid as RECEIPT_ID, rn.porderid as PO, rn.preceipt_symbol as SYMBOL, convert(varchar,rn.entry_date,23) as ENTRY_DATE,su.full_name as FULL_NAME,ml.loc_name as LOCATION, rd.icitem_symbol as PARTNO, rd.icitem_name as ITEM_DESC,
        u.ui_name as UNIT, rd.qty as QTY, rd.net_price as NET_PRICE, (rd.qty*rd.net_price) as TOTAL
        from prc_receipt_notes rn
        LEFT OUTER JOIN sec_users su ON rn.insert_userid=su.userid
        LEFT OUTER JOIN mst_locations ml ON rn.preceipt_locid=ml.locid
        LEFT OUTER JOIN prc_receipt_notes_details rd ON rn.preceiptid=rd.preceiptid
        LEFT OUTER JOIN inv_units_of_issue u ON rd.ui_id=u.ui_id
        where porderid=".$po."";

        $po_received = DB::select($statement);
        
        // dd($po_datails);
        if(count($po_received) > 0)
        {
            return response()->json([
                'status' => 200,
                'message' => 'Data was found',
                'count' => count($po_received),
                'PO_Received' => $po_received
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 422,
                'message' => 'No PO Found!',
                'PO_Received' => count($po_received)
            ], 422);
        }
    }
    public function POInvoiced(int $po)
    {
        $statement= "select pv.pinvoiceid as INVOICE_ID, pv.porderid as PO, pv.pinvoice_symbol as INVOICE_SYMBOL,convert(varchar,pv.entry_date,23) as INVOICE_DATE,
        pv.pinvoice_desc as INVOICE_DESC, pv.net_amt as INVOICE_AMT, mc.currency_name as CUR,
        pv.vinvoice_ref as INVOICE_REF, pt.payterm_name as PAY_TERM, convert(varchar,pv.due_date,23) as DUE_DATE, pv.pp_amt as PAID_AMT,
        s.full_name as INVOICED_BY
        from prc_invoices pv
        LEFT OUTER JOIN mst_currencies mc ON pv.currencyid=mc.currencyid
        LEFT OUTER JOIN mst_payment_terms pt ON pt.paytermid = pv.paytermid
        LEFT OUTER JOIN sec_users s ON s.userid=pv.insert_userid
        where pv.porderid=".$po."";

        $po_invoiced = DB::select($statement);
        
        // dd($po_datails);
        if(count($po_invoiced) > 0)
        {
            return response()->json([
                'status' => 200,
                'message' => 'Data was found',
                'count' => count($po_invoiced),
                'PO_Invoiced' => $po_invoiced
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 422,
                'message' => 'No PO Found!',
                'PO_Invoiced' => count($po_invoiced)
            ], 422);
        }
    }
    public function POPaid(int $po)
    {
        $statement= "select PP.ppaymentid as PAY_ID, PP.porderid as PO, convert(varchar,PP.entry_date,23) as PAY_DATE, PP.ppayment_desc as PAY_DESC,
        PP.pay_amt as PAY_AMT, mc.currency_name as CUR, pm.paymethod_name as PAY_METHOD,ba.bankacc_name as BANK_ACC, PP.cheque_ref as PAY_REF,
        pp.prepay_flag as PAY_FALG,s.full_name as PAID_BY, pp.ppayment_symbol as PAY_CODE
        from prc_payments pp
        LEFT OUTER JOIN mst_payment_methods pm ON pp.paymethodid=pm.paymethodid
        LEFT OUTER JOIN mst_banks_accounts ba ON pp.bankaccid=ba.bankaccid
        LEFT OUTER JOIN mst_currencies mc ON pp.currencyid=mc.currencyid
        LEFT OUTER JOIN sec_users s ON s.userid=pp.insert_userid
        where isnull(pp.porderid, 0) = ".$po."
        union all
        select pp.ppaymentid,pp.porderid,convert(varchar,PP.entry_date,23), pp.ppayment_desc,
        sum(ppd.src_pay_amt),mc.currency_name,pm.paymethod_name,ba.bankacc_name,pp.cheque_ref,pp.prepay_flag,s.full_name,
        pp.ppayment_symbol
        from prc_payments pp, prc_payments_details ppd,
        v_prc_transactions vw,mst_payment_methods pm,mst_banks_accounts ba,mst_currencies mc,sec_users s
        where pp.ppaymentid = ppd.ppaymentid and ppd.src_transtypeid = vw.transtypeid and pp.paymethodid=pm.paymethodid
        and pp.bankaccid=ba.bankaccid and pp.insert_userid=s.userid and mc.currencyid=pp.currencyid
        and ppd.src_refid = vw.transid
        and isnull(pp.porderid, 0) = 0
        and pp.usage_amt > 0
        and vw.porderid = ".$po."
        group by pp.ppaymentid,pp.ppayment_symbol,pp.porderid,convert(varchar,PP.entry_date,23), pp.ppayment_desc,
        pp.pay_amt,mc.currency_name,pm.paymethod_name,ba.bankacc_name,pp.cheque_ref,pp.prepay_flag,s.full_name,
        pp.ppayment_symbol";

        $po_paid = DB::select($statement);
        
        // dd($po_datails);
        if(count($po_paid) > 0)
        {
            return response()->json([
                'status' => 200,
                'message' => 'Data was found',
                'count' => count($po_paid),
                'PO_Paid' => $po_paid
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 422,
                'message' => 'No PO Found!',
                'PO_Paid' => count($po_paid)
            ], 422);
        }
    }
    // ===================================== SALES ========================================
    public function SOReport(Request $request, int $userid)
    {
        $statement = ("select * from (
            select #SONUM,CUST_PO_DATE,ANNOUNCE_DATE,DELIVERY_DATE,PROJECT_NAME,PROJECT_MGR,CUSTOMER_NAME,CUSTOMER_TYPE,BRANCH_NAME,
            SALES_PERSON,PAY_TERM,CUR_NAME,SO_TOTAL_AMT,PLANNED_COST,(((SO_TOTAL_AMT-PLANNED_COST)/NULLIF(PLANNED_COST,0))*100) as PLANNEDMARGIN,
            (DUES_PERC*SO_TOTAL_AMT)/100 as DUES_AMT,
            case when DEL_ITEM_QTY = 0 then (case when (select count(*) from sls_delivery_notes do where do.sorderid=#SONUM) > 0 then 'Partial' else 'None' end)
            when (DEL_ITEM_QTY > 0 and DEL_ITEM_QTY < SO_ITEM_QTY) then 'Partial'
            when (DEL_ITEM_QTY >= SO_ITEM_QTY) then 'Completed' end as DELIVERY_STATUS,TOT_INVOICE,PAID_AMT,
            case when (DEL_ITEM_QTY = 0 and PAID_AMT = 0 and isnull(((DUES_PERC*SO_TOTAL_AMT)/100),0) > 0) then 'Issued'
            when (((DEL_ITEM_QTY > 0) and (DEL_ITEM_QTY < SO_ITEM_QTY)) or (PAID_AMT > 0 and DEL_ITEM_QTY < SO_ITEM_QTY) or (PAID_AMT = 0 and DEL_ITEM_QTY =0 and isnull(((DUES_PERC*SO_TOTAL_AMT)/100),0) = 0)) then 'Active'
            when ((DEL_ITEM_QTY >= SO_ITEM_QTY) and PAID_AMT < SO_TOTAL_AMT) then 'Completed'
            when ((DEL_ITEM_QTY >= SO_ITEM_QTY) and PAID_AMT >= SO_TOTAL_AMT) then 'Closed'
            end as SO_STATUS,TOT_PO,SO_ITEM_QTY,DEL_ITEM_QTY,
            SO_AMT_WITHOUT_TAX,CUSTOMER_ID,BRANCH_ID,CUS_TYP_ID,SALES_PER_ID,PRO_MGR_ID,SO_SYMBOL,
            POST_FLAG,MGR_CODE,SALES_PERSON_ID
         from(
            select so.sorderid as #SONUM, convert(varchar,so.entry_date,23) as CUST_PO_DATE,
            convert(varchar,so.activation_date,23) as ANNOUNCE_DATE,convert(varchar,so.delivery_date,23) as DELIVERY_DATE,
            pp.project_symbol as PROJECT_NAME, pm.projectmanager_name as PROJECT_MGR, c.customer_name as CUSTOMER_NAME,
            ct.ctype_name as CUSTOMER_TYPE, b.branch_name as BRANCH_NAME, sp.salesperson_name as SALES_PERSON, pt.payterm_name as PAY_TERM,
            cu.currency_name as CUR_NAME, so.net_amt as SO_TOTAL_AMT,
            (select sum(od.qty*od.unit_cost) from sls_sales_orders_details od where od.sorderid=so.sorderid) as PLANNED_COST,
            so.si_amt as TOT_INVOICE, so.sp_amt as PAID_AMT, so.po_amt as TOT_PO,so.subtot_amt as SO_AMT_WITHOUT_TAX,
            (select (select sum(pt.pay_perc) from mst_payment_terms_details pt where pt.paytermid = sso.paytermid
            and (
            (pt.transtypeid = (select tt.transtypeid from mst_transactions_types tt where tt.transtype_symbol = 'SO')
            and datediff(day, sso.entry_date, getdate()) >= pt.nof_days)
            or (pt.transtypeid = (select tt.transtypeid from mst_transactions_types tt where tt.transtype_symbol = 'SI')
            and datediff(day, (select min(si.entry_date) from sls_invoices si where si.sorderid = sso.sorderid), getdate()) >= pt.nof_days)
            or (pt.transtypeid = (select tt.transtypeid from mst_transactions_types tt where tt.transtype_symbol = 'DN')
            and  datediff(day,(select min(dn.entry_date) from sls_delivery_notes dn where dn.sorderid = sso.sorderid), getdate()) >= pt.nof_days)
            or (pt.transtypeid = (select tt.transtypeid from mst_transactions_types tt where tt.transtype_symbol = 'DN')
            and isnull((select count(*) from inv_items it where it.icitemid in (select sod.icitemid from sls_sales_orders_details sod where sod.sorderid = sso.sorderid)
            and it.stk_flag = 1), 0) = 0 and datediff(day, (select min(si.entry_date) from sls_invoices si where si.sorderid = sso.sorderid), getdate()) >= pt.nof_days)
            )) from sls_sales_orders sso where sso.sorderid = so.sorderid) as DUES_PERC,
            (select sum(sod.qty) from sls_sales_orders_details sod where sod.sorderid=so.sorderid) as SO_ITEM_QTY,
            (select sum(case when sd.dn_qty > sd.si_qty then sd.dn_qty else sd.si_qty end) from sls_sales_orders_details sd where sd.sorderid=so.sorderid) as DEL_ITEM_QTY,
            c.customerid as CUSTOMER_ID ,ct.ctypeid as CUS_TYP_ID,b.branchid as BRANCH_ID,sp.salespersonid as SALES_PER_ID,
            pm.projectmanagerid as PRO_MGR_ID,so.sorder_symbol as SO_SYMBOL,so.post_flag as POST_FLAG, pm.empid as MGR_CODE,sp.salespersonid as SALES_PERSON_ID
            from sls_sales_orders so
            LEFT OUTER JOIN mst_branches b ON so.branchid=b.branchid
            LEFT OUTER JOIN sls_invoices sli ON sli.sorderid=so.sorderid
            LEFT OUTER JOIN sls_sales_orders_details od ON so.sorderid=od.sorderid
            LEFT OUTER JOIN sls_salespersons SP ON so.salespersonid=sp.salespersonid
            LEFT OUTER JOIN sls_customers c ON so.customerid=c.customerid
               LEFT OUTER JOIN sls_customers_types ct ON c.ctypeid=ct.ctypeid
            LEFT OUTER JOIN ppm_projects_managers pm ON so.projectmanagerid=pm.projectmanagerid
            LEFT OUTER JOIN ppm_projects pp ON so.sorderid=pp.sorderid
            LEFT OUTER JOIN mst_currencies cu ON so.currencyid=cu.currencyid
            LEFT OUTER JOIN mst_payment_terms pt ON so.paytermid=pt.paytermid
         )mytable
      )mytable
      WHERE (mytable.POST_FLAG=1 and (mytable.BRANCH_ID in (select b.branchid from mst_branches b,sec_users_branches Ub,sec_users U where b.branchid=Ub.branchid and ub.userid=U.userid and u.userid=".$userid.") or (".$userid." IN (SELECT SC.USERID FROM sec_users_transactions_states SC WHERE SC.transtypeid=(SELECT transtypeid FROM mst_transactions_types WHERE transtype_symbol ='SOR') and SC.stateid=100))))");

        if($request->date_from != null)
        {
            $statement .= " and CUST_PO_DATE >= CONVERT(varchar,'".$request->date_from."', 23)";
        }
        if($request->date_to != null)
        {
            $statement .= " and CUST_PO_DATE <= CONVERT(varchar,'".$request->date_to."', 23)";
        }
        if($request->branch != null)
        {
            $statement .= " and BRANCH_ID=".$request->branch."";
        }
        if($request->project != null)
        {
            $statement .= " and upper(PROJECT_NAME) LIKE upper('%{$request->project}%')";
        }
        if($request->po_code != null)
        {
            $statement .= " and upper(mytable.PO_SYMBOL) like upper('%{$request->po_code}%')";
        }
        if($request->po_status != null && $request->po_status != "Non-Closed")
        {
            $statement .= " and mytable.PO_STATUS='".$request->po_status."')";
        }
        if($request->po_status != null && $request->po_status == "Non-Closed")
        {
            $statement .= " and mytable.PO_STATUS != 'Closed'";
        }
        if($request->rec_status != null)
        {
            $statement .= " and mytable.RECEIVING_STATUS='".$request->rec_status."'";
        }
        if($request->dues != null)
        {
            $statement .=" and (DUES_AMT > PO_PAID_AMT and (DUES_AMT - PO_PAID_AMT) > 5)";
        }
        

        $statement .= ' group by mytable.PO_ID,mytable.PO_SYMBOL,mytable.PO_DATE,mytable.PO_DeliveryDate,mytable.PAYMENT_DATE,mytable.ACTUAL_DEL_DATE,
                        mytable.SUPPLIER,mytable.PROJECT_NAME,mytable.CUSTOMER,mytable.BRANCH,mytable.PAY_TERM,mytable.CUR_NAME,mytable.PO_AMT,
                        mytable.DUES_AMT,mytable.RECEIVING_STATUS,mytable.PO_PAID_AMT,PO_STATUS,mytable.DELIVERY_STATUS,mytable.CUST_PO_DATE,
                        mytable.ANNOUNCE_DATE,mytable.POS_FALG,mytable.PO_ITEMS_QTY,mytable.RECEIVE_ITEM_QYT,mytable.DEL_ITEM_QYT,mytable.SO,mytable.MGR_CODE
                        order by mytable.PO_SYMBOL';
        $po_report = DB::select($statement);
        
        // dd($po_report);
        if(count($po_report) > 0)
        {
            return response()->json([
                'status' => 200,
                'message' => '',
                'count' => count($po_report),
                'PO_Data' => $po_report
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 422,
                'message' => 'No PO Found!',
                'branches' => count($po_report)
            ], 422);
        }
    }
    // =========================================================================================

    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|string|email',
    //         'password' => 'required|string',
    //     ]);
    //     $credentials = $request->only('email', 'password');
    //     $token = Auth::attempt($credentials);
        
    //     if (!$token) {
    //         return response()->json([
    //             'message' => 'Unauthorized',
    //         ], 401);
    //     }

    //     $user = Auth::user();
    //     return response()->json([
    //         'user' => $user,
    //         'authorization' => [
    //             'token' => $token,
    //             'type' => 'bearer',
    //         ]
    //     ]);
    // }

    // public function register(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:6',
    //     ]);

    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //     ]);

    //     return response()->json([
    //         'message' => 'User created successfully',
    //         'user' => $user
    //     ]);
    // }

    // public function logout()
    // {
    //     Auth::logout();
    //     return response()->json([
    //         'message' => 'Successfully logged out',
    //     ]);
    // }

    // public function refresh()
    // {
    //     return response()->json([
    //         'user' => Auth::user(),
    //         'authorisation' => [
    //             'token' => Auth::refresh(),
    //             'type' => 'bearer',
    //         ]
    //     ]);
    // }
}
