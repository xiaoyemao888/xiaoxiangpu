<?php
/**
 * Created by PhpStorm.
 * User: LENOVO
 * Date: 2020/10/5
 * Time: 22:06
 */
namespace app\admin\controller\product;

use app\common\controller\Backend;
use app\Request;
use think\Exception;
use app\common\model\Shops;
use app\admin\model\Category;
use app\common\model\Product;
use app\common\model\ProductSku;
use app\common\model\ProductDetails;
class Goods extends Backend
{
    protected $model = null;
    protected $skuModel = null;
    public function initialize(){
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->model = new Product();
        $this->skuModel = new ProductSku();
        //所属分类
        $cate = (new Category)->where('status', 1)->order('createtime', 'desc')->select();
        $category = [0 => __('None')];
        foreach ($cate as $k => $v) {
            $category[$v['id']] = $v['cate_name'];
        }
        //所属店铺
        $shops = (new Shops) -> where('status',1) -> order('createtime asc') -> select();
        $shopslist =[0 => __('None')];
        foreach ($shops as $k => $v) {
            $shopslist[$v['id']] = $v['title'];
        }
        $this -> assign('shopslist',$shopslist);
        $this->assign('category', $category);
    }

    public function index (Request $request){
        if ($request->isAjax()) {
            if ($request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('status', 1)
                ->count();
            $list = $this->model
                ->where($where)
                ->where('status', 1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this-> fetch();
    }

    /**
     * 商品添加
     * @return string
     * @throws \Exception
     */
    public function add(){
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
//            dump($params);die;
            if($params){
                $skus = $this->request->post('rowsku/a');
                $details = $this->request->post('details/a');
                $spec = $this->request->post('spec/a');
                try {
                    $params['pcid'] = (new Category)::where('id',$params['category_id']) -> field('pid')->find()->toArray()['pid'];
                    $params['images'] = explode(',',$details['images_url'])[0];
                    $params['inventory'] = array_sum($skus['stock']);
                    $params['product_spec_info'] = $this->getSpenInfo($spec['spec_name'], $spec['spec_value']);
                    $params['createtime'] = time();
                    $this->model->save($params);
                    $details['product_id'] = $this->model->id;
                    $details['createtime'] = time();
                    (new ProductDetails) -> save($details);
                    $sku = $this->getSkuInfo($skus['sku_title'], $skus['sku_price'], $skus['stock'], $this->model->id);
                    $this->skuModel->saveAll($sku);
                    $this->success();
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error();
        }
        return $this-> fetch();
    }

    /**
     * 商品修改
     * @param null $ids
     * @return string
     * @throws \Exception
     */
    public function edit ($ids = null){
        $product = $this->model->find($ids);
//        $details = (new ProductDetails)::where('product_id',$ids) -> find();
        //sku属性
        $sku = $product->skus()->select();
        $details = $product ->  productdetails() -> find();
        $this->assign('sku', $sku);
        $specinfo = json_decode($product->product_spec_info, 1);
        $spec['spec_name'] =$specinfo['name'];
        $spec['spec_value'] = implode('-', $specinfo['list']);
        $this->assign('spec', $spec);
        $this->assign('details', $details);

        if (! $product) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            $spec = $this->request->post('spec/a');
            $skus = $this->request->post('rowsku/a');
            $details = $this->request->post('details/a');
            try {
                $params['pcid'] = (new Category)::where('id',$params['category_id']) -> field('pid')->find()->toArray()['pid'];
                $params['images'] = explode(',',$details['images_url'])[0];
                $params['inventory'] = array_sum($skus['stock']);
                $params['product_spec_info'] = $this->getSpenInfo($spec['spec_name'], $spec['spec_value']);
                $params['updatetime'] = time();
                $product->save($params);
                $skuInfo = $this->getSkuInfo($skus['sku_title'], $skus['sku_price'], $skus['stock'], $ids);
                $this->skuModel->where('product_id', $ids)->delete();
                $this->skuModel->saveAll($skuInfo);
                (new ProductDetails) -> where('product_id',$ids) -> update($details);
                $this->success($ids);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->assign('row', $product);
        return $this->fetch();
    }

    public function getSkuInfo ($skuTitle, $skuPrice, $skuStock, $pid) {
        $sku = [];
        foreach ($skuTitle as $k => $v) {
            $sku[$k]['product_id'] = $pid;
            $sku[$k]['title'] = $v;
            $sku[$k]['price'] = $skuPrice[$k];
            $sku[$k]['stock'] = $skuStock[$k];
        }
        return $sku;
    }

    public function getSpenInfo ($specName, $specValue)
    {
        $spec = explode('-', $specValue);
        $specInfo = [
            'name' => $specName,
            'list' => $spec
        ];
        $result = json_encode($specInfo);
        return $result;
    }

    /**
     * 删除类目
     * @param string $ids
     * @throws \Exception
     */
    public function del ($ids = '')
    {
        if ($ids) {
            $goods = $this->model->find($ids);
            $goods->status = 9;
            $goods->save();
            $this->success();
        }
        $this->error();
    }
}
