<?php

namespace app;


use PDO;

class Receipt implements Purchase_Record
{
    public array $line_items = [];
    private Id $id;

    public function __construct(string $id){
        if (empty($id) ) {
            $id = 'receipt_';
        }
        $this->id = new Id(uniqid($id, false));

    }

    public function id(): ID {
        return $this->id;
    }

    public  function add_item(Line_Item $item){
        $this->line_items[] = $item;
    }

    //Sums the total of the line item subtotals.
    public function subtotal(): int {
        return array_reduce($this->line_items, function($accum, $item){
             return $accum + $item->subtotal();
        },0);
    }

    //Calculate the amount of gst tax.
    public function gst_amount() : float    {
        return array_reduce($this->line_items, function($carry, $li){
            return $carry + $li->gst();
        },0.0);
    }

    //Calculate the amount of gst tax.
    public function pst_amount() : float    {
        return array_reduce($this->line_items, function($carry, $li){
            return $carry + $li->pst();
        },0.0);
    }
    //Total gst & pst.
    public function tax(): float {
        return array_reduce($this->line_items, function($carry, $li){
            return $carry + $li->tax()/100;
        },0.0);
    }

    //add all the tax amounts and the subtotal.
    public function total() : float {
          return $this->tax() + $this->subtotal()/100;
    }

    public function create_sql():array {
        return array_map(function ($i) {
            return $i->sql_values();
        }, $this->line_items );
    }

}
