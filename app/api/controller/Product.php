<?php
namespace app\api\controller;
use app\admin\model\Category;
use app\BaseController;
//use app\common\SearchBuilders\ProductSearchBuilder;
//use app\Exceptions\InvalidRequestException;
//use Illuminate\Http\Request;
//use Illuminate\Pagination\LengthAwarePaginator;
use app\common\model\Cart;
use app\common\model\ProductDetails;
use app\common\model\ProductSku;
use app\common\model\User;
use think\facade\Db;
use app\Request;
use app\common\model\Product as PModel;
use app\common\model\ProductComment;
class Product extends BaseController{
    //产品列表
    public function productlist(Request $request){
        if (!$request->isPost()) return apiBack('fail', '请求方式错误', '10004');
        $type = $request->post('type');
        $keyword = $request->post('keyword');
        $uid = $request->post('uid');
//        $cid = $request->post('cid') ?? 0;
        $where = "status=1";
        if (!empty($keyword) && isset($keyword)) {
            $where .= " and name like '%$keyword%'";
        }
        $orderby = "createtime desc";
        $page = $request->post('page') ?? 0;
        $limit = 20;
        $cate = [];
        //0元购 分享人数
        $share_user = 0;
        $db = Category::where('status', 1)->order('createtime', 'desc')->field('id, cate_name');
        switch ($type){
            case 'ms':
                $where.=" and is_rush=1";
                break;
            case 'jmj':
                $where.=" and category_id=2";
                $cate = $db->where('pid', 2)->select()->toArray();
                break;
            case 'xjtcp':
                $where.=" and category_id=3";
                $cate = $db->where('pid', 3)->select()->toArray();
                break;
            case 'group':
                $where.=" and spell_group > 1";
                break;
            case 'buy0':
                $where.=" and buy0=1";
                $share_user = User::where('invitecode', $uid)->count();
                break;
        }
        $productsfild = 'id,name,images,price,discount_price,shop_id,category_id,sales,is_rush';
//        $list = (new PModel)::productlist($where,$productsfild,$orderby,$page,$limit);
        $list = (new PModel)::with('shops')->where($where)
            -> field($productsfild)
            -> order('createtime desc')
            -> select()  -> toArray();
        $data =[
            'secskill' => ['skill_start'=>strtotime(C('skill_start')) - time(),'skill_end'=>strtotime(C('skill_end')) - time()],
            'data' => $list,
            'cate' => $cate,
            'share_user_count' => $share_user
        ];
        return apiBack('success', '成功', '10000', $data);
    }

    //商品详情
    public function productdetails(Request $request){
        if (!$request->isPost()) return apiBack('fail', '请求方式错误', '10004');
        $productid = $request->post('id');
        if(empty($productid)){
            return apiBack('fail', '商品id不能为空', '10004');
        }
        // 可以使用闭包查询
        $pdetails = (new PModel)::field('id,category_id,name,images,price,discount_price,shop_id,sales,rating,review,product_spec_info,parea,is_rush')
        ->find($productid) -> toArray();
        $details = (new ProductDetails)::find($productid);
        $pdetails['images_url'] = $details->images_url;
        $pdetails['picdesc'] = $details->picdesc;
        $pdetails['introduce'] = $details->introduce;
        $pdetails['comment_num'] = ProductComment::where('product_id', $productid)->count();
        $pdetails['product_spec_info'] = json_decode($pdetails['product_spec_info'],true);
        if($pdetails['is_rush'] == 1){
            $pdetails['secskill'] = ['skill_start'=>strtotime(C('skill_start')) - time(),'skill_end'=>strtotime(C('skill_end')) - time()];
        }
        //:with(['productdetails'=>function($query){$query->field('product_id,images_url,picdesc,introduce');}])
//            -> find() ->toArray();
        $skulist = (new ProductSku)::where('product_id',$productid)  -> field('id as skuid,title,price as skuprice,stock') -> select()->toArray();
        foreach ($skulist as $key => $val){
            $res=app('redis')->llen('goods_store'.$productid.$val['skuid']);
            $count=$val['stock']-$res;
            for($i=0;$i<$count;$i++){
                app('redis') ->lpush('goods_store'.$productid.$val['skuid'],1);
            }
//            dump(app('redis')->llen('goods_store'.$productid.$val['skuid']));die;
        }
        $data =[
            'details'=>$pdetails,
            'skulist'=> $skulist,
            'secskill' => ['skill_start'=>strtotime(C('skill_start')) - time(),'skill_end'=>strtotime(C('skill_end')) - time()]
        ];
        return apiBack('success', '成功', '10000', $data);
    }

    //产品评价
    public function productevaluation(Request $request){
        if (!$request->isPost()) return apiBack('fail', '请求方式错误', '10004');
        $productid = $request->post('id');
        $details = (new ProductComment)::with(['product','user']) -> where('id',$productid)
            -> order('createtime desc')
            -> limit(0,20)
            -> select() -> toArray();
        return apiBack('success', '成功', '10000', $details);
    }






    //添加商品到购物车
    public function addcart(){
        //https://blog.csdn.net/yanhui_wei/article/details/8585509
//        https://blog.csdn.net/weixin_30333885/article/details/98378934
//        https://blog.csdn.net/Gekkoou/article/details/88714674
//        https://blog.csdn.net/qq_42573785/article/details/105815508
        //点击添加购物车按钮时，传递过来两个参数
        $product_spec_id = isset ( $_GET ['spid'] ) ? intval ( $_GET ['spid'] ) : 0;
        //产品规格id：product_spec_id,对应product_spec_id表中product_spec_id字段的值
        $quantity = isset ( $_GET ['num'] ) ? intval ( $_GET ['num'] ) : 0;//产品数量

    }

}
