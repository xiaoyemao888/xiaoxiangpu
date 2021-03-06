<?php


namespace app\common\model;


class ProductSku extends BaseModel
{
//    public function product ()
//    {
//        return $this->belongsTo(Product::class);
//    }
    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function skucartitem(){
        return $this -> hasMany(Cart::class);
    }

    public function ordersdetail(){
        return $this -> hasOne(OrdersDetail::class);
    }
//    public function decreaseStock($amount)
//    {
//        if ($amount < 0) {
//            throw new InternalException("减库存不可小于0");
//        }
//
//        return $this->where('id',$this->id)->where('stock','>=',$amount)->decrement('stock',$amount);
//    }
//
//    public function addStock($amount)
//    {
//        if ($amount < 0) {
//            throw new InternalException('加库存不可小于0');
//        }
//        $this->increment('stock', $amount);
//    }
}
