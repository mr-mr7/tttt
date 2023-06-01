<?php

// تراز اولیه کاربران
$inits = Product::query()
    ->join('categories', 'categories.id', 'products.category_id')
    ->join('user_inits', 'products.id', 'user_inits.product_id')
    ->groupBy(['user_inits.product_id', 'user_inits.user_id'])
    ->when($user_id, function ($q) use ($user_id) {
        $q->where('user_inits.user_id', $user_id);
    })
    ->select([
        \DB::raw('user_inits.user_id as user_id'),
        'categories.title as cat_title', 'categories.id as cat_id', 'categories.slug as cat_slug',
        'products.title', 'products.id', 'products.sell_price', 'products.buy_price', 'products.unit',

// موجودی با واحد همون محصول
        \DB::raw('user_inits.init as inventory'),

// تعداد تراکش ها
        \DB::raw('0 as count'),

// وزن 750 گرم برای طلاها
        \DB::raw('user_inits.init as gold_weight750'),

// وزن 750 گرم برای سکه ها
        \DB::raw('(user_inits.init * products.w750) as coin_weight750'),
    ]);

//محصولات دریافت و پرداخت
$products_PR = Product::query()
    ->join('categories', 'categories.id', 'products.category_id')
    ->join('stocks', 'products.id', 'stocks.product_id')
    ->join(DB::raw('products as product_parent'), 'products.parent_id', 'product_parent.id')
    ->when(isset($filters['start_created_at']), function ($q) use ($filters) {
        $q->where('stocks.created_at', '>=', "{$filters['start_created_at']}");
    })
    ->when(isset($filters['end_created_at']), function ($q) use ($filters) {
        $q->where("stocks.created_at", "<=", "{$filters['end_created_at']}");
    })
    ->when(isset($filters['stock_id_not_in']), function ($q) use ($filters) {
        $q->whereNotIn('stocks.id', $filters['stock_id_not_in']);
    })
    /*->leftJoin('input_items as in1', 'in1.stock_id', 'stocks.id')
    ->leftJoin('output_items as out', 'out.stock_id', 'stocks.id')
    ->leftJoin('input_items as in2', 'out.input_item_id', 'in2.stock_id')*/
    ->groupBy(['product_parent.id', 'stocks.user_id'])
    ->when($user_id, function ($q) use ($user_id) {
        $q->where('stocks.user_id', $user_id);
    })
    ->select([
        \DB::raw('stocks.user_id as user_id'),
        'categories.title as cat_title', 'categories.id as cat_id', 'categories.slug as cat_slug',
        'product_parent.title', 'product_parent.id', 'product_parent.sell_price', 'product_parent.buy_price', 'product_parent.unit',

// موجودی با واحد همون محصول
        \DB::raw('SUM(stocks.amount * stocks.type) as inventory'),
// تعداد تراکش ها
        \DB::raw('COUNT(stocks.id) as count'),
// وزن 750 گرم برای طلاها
//                \DB::raw('SUM(stocks.amount * COALESCE(in1.carat,in2.carat) * stocks.type / 750) as gold_weight750'),
        \DB::raw('SUM((stocks.amount * product_parent.w750) * stocks.type) as gold_weight750'),
// وزن 750 گرم برای سکه ها
        \DB::raw('SUM((stocks.amount * product_parent.w750) * stocks.type) as coin_weight750'),
    ]);


//محصولات خرید و فروش
$products_BS = Product::query()
    ->join('categories', 'categories.id', 'products.category_id')
    ->join('orders', 'products.id', 'orders.product_id')
    ->when(isset($filters['start_created_at']), function ($q) use ($filters) {
        $q->where('orders.created_at', '>=', "{$filters['start_created_at']}");
    })
    ->when(isset($filters['end_created_at']), function ($q) use ($filters) {
        $q->where("orders.created_at", "<=", "{$filters['end_created_at']}");
    })
    ->when(isset($filters['order_id_not_in']), function ($q) use ($filters) {
        $q->whereNotIn('orders.id', $filters['order_id_not_in']);
    })
    ->where('orders.status', 100)
    ->groupBy(['orders.product_id', 'orders.user_id'])
    ->when($user_id, function ($q) use ($user_id) {
        $q->where('orders.user_id', $user_id);
    })
    ->select([
        \DB::raw('orders.user_id as user_id'),
        'categories.title as cat_title', 'categories.id as cat_id', 'categories.slug as cat_slug',
        'products.title', 'products.id', 'products.sell_price', 'products.buy_price', 'products.unit',

// موجودی با واحد همون محصول
        \DB::raw('SUM(orders.amount * orders.type * -1) as inventory'),

// تعداد تراکش ها
        \DB::raw('COUNT(orders.id) as count'),

// وزن 750 گرم برای طلاها
        \DB::raw('SUM(orders.amount * orders.type * -1 ) as gold_weight750'),

// وزن 750 گرم برای سکه ها
        \DB::raw('SUM((orders.amount * products.w750) * orders.type * -1 ) as coin_weight750'),
    ]);


// محصولات خرید و فروش برای ریال
$rial_cat = Category::query()->where('slug', 'rial')->first();
$rial_product = $rial_cat->products()->first();
$products_BS_RIAL = false;
if ($rial_product) {
//محصولات خرید و فروش برای تراز ریالی
    $products_BS_RIAL = Product::query()
        ->join('categories', 'categories.id', 'products.category_id')
        ->join('orders', 'products.id', 'orders.product_id')
        ->when(isset($filters['start_created_at']), function ($q) use ($filters) {
            $q->where('orders.created_at', '>=', "{$filters['start_created_at']}");
        })
        ->when(isset($filters['end_created_at']), function ($q) use ($filters) {
            $q->where("orders.created_at", "<=", "{$filters['end_created_at']}");
        })
        ->when(isset($filters['order_id_not_in']), function ($q) use ($filters) {
            $q->whereNotIn('orders.id', $filters['order_id_not_in']);
        })
        ->where('orders.status', 100)
        ->groupBy(['orders.user_id'])
        ->when($user_id, function ($q) use ($user_id) {
            $q->where('orders.user_id', $user_id);
        })
        ->select([
            \DB::raw('orders.user_id as user_id'),
            DB::raw("'$rial_cat->title' as cat_title"), DB::raw("$rial_cat->id as cat_id"), DB::raw("'$rial_cat->slug' as cat_slug"),
            DB::raw("'$rial_product->title' as title"), DB::raw("$rial_product->id as id"), DB::raw("1 as sell_price"), DB::raw("1 as buy_price"), DB::raw("$rial_product->unit as unit"),

// موجودی با واحد همون محصول
            \DB::raw('SUM(total_price*orders.type) as inventory'),

// تعداد تراکش ها
            \DB::raw('0 as count'),

// وزن 750 گرم برای طلاها
            \DB::raw('0 as gold_weight750'),

// وزن 750 گرم برای سکه ها
            \DB::raw('0 as coin_weight750'),
        ]);
}

// محصولات حواله پرداخت
$remittances_payer = Product::query()
    ->join('categories', 'categories.id', 'products.category_id')
    ->join('remittances', 'products.id', 'remittances.product_id')
    ->when(isset($filters['start_created_at']), function ($q) use ($filters) {
        $q->where('remittances.created_at', '>=', "{$filters['start_created_at']}");
    })
    ->when(isset($filters['end_created_at']), function ($q) use ($filters) {
        $q->where("remittances.created_at", "<=", "{$filters['end_created_at']}");
    })
    ->when(isset($filters['remittance_id_not_in']), function ($q) use ($filters) {
        $q->whereNotIn('remittances.id', $filters['remittance_id_not_in']);
    })
    ->where('remittances.status', 100)
    ->groupBy(['remittances.product_id', 'remittances.payer_id'])
    ->when($user_id, function ($q) use ($user_id) {
        $q->where('remittances.payer_id', $user_id);
    })
    ->select([
        \DB::raw('remittances.payer_id as user_id'),
        'categories.title as cat_title', 'categories.id as cat_id', 'categories.slug as cat_slug',
        'products.title', 'products.id', 'products.sell_price', 'products.buy_price', 'products.unit',

// موجودی با واحد همون محصول
        \DB::raw('SUM(remittances.amount * -1) as inventory'),

// تعداد تراکش ها
        \DB::raw('COUNT(remittances.id) as count'),

// وزن 750 گرم برای طلاها
        \DB::raw('SUM(remittances.amount) as gold_weight750'),

// وزن 750 گرم برای سکه ها
        \DB::raw('SUM((remittances.amount * products.w750) * -1) as coin_weight750'),
    ]);

// محصولات حواله دریافت
$remittances_recipient = Product::query()
    ->join('categories', 'categories.id', 'products.category_id')
    ->join('remittances', 'products.id', 'remittances.product_id')
    ->when(isset($filters['start_created_at']), function ($q) use ($filters) {
        $q->where('remittances.created_at', '>=', "{$filters['start_created_at']}");
    })
    ->when(isset($filters['end_created_at']), function ($q) use ($filters) {
        $q->where("remittances.created_at", "<=", "{$filters['end_created_at']}");
    })
    ->when(isset($filters['remittance_id_not_in']), function ($q) use ($filters) {
        $q->whereNotIn('remittances.id', $filters['remittance_id_not_in']);
    })
    ->where('remittances.status', 100)
    ->groupBy(['remittances.product_id', 'remittances.recipient_id'])
    ->when($user_id, function ($q) use ($user_id) {
        $q->where('remittances.recipient_id', $user_id);
    })
    ->select([
        \DB::raw('remittances.recipient_id as user_id'),
        'categories.title as cat_title', 'categories.id as cat_id', 'categories.slug as cat_slug',
        'products.title', 'products.id', 'products.sell_price', 'products.buy_price', 'products.unit',

// موجودی با واحد همون محصول
        \DB::raw('SUM(remittances.amount * 1) as inventory'),

// تعداد تراکش ها
        \DB::raw('COUNT(remittances.id) as count'),

// وزن 750 گرم برای طلاها
        \DB::raw('SUM(remittances.amount) as gold_weight750'),

// وزن 750 گرم برای سکه ها
        \DB::raw('SUM((remittances.amount * products.w750) * 1) as coin_weight750'),
    ])
    ->unionAll($inits)
    ->unionAll($products_PR)
    ->unionAll($products_BS)
    ->when($products_BS_RIAL, function ($q) use ($products_BS_RIAL) {
        $q->unionAll($products_BS_RIAL);
    })
    ->unionAll($remittances_payer);

$remittances_recipient_sql = $remittances_recipient->toRawSql();

$users = DB::query()
    ->fromSub($remittances_recipient, 'results')
    ->join('users', 'users.id', 'user_id')
    ->groupBy('id', 'user_id')
    ->select([
        'users.name',
        'user_id', 'cat_title', 'cat_id', 'cat_slug', 'title', 'results.id', 'sell_price', 'buy_price', 'unit',
// موجودی با واحد همون محصول
        \DB::raw('SUM(inventory) as inventory'),

// تعداد تراکش ها
        \DB::raw('SUM(count)'),

// وزن 750 گرم برای طلاها
        \DB::raw('SUM(gold_weight750) as gold_weight750'),

// وزن 750 گرم برای سکه ها
        \DB::raw('SUM(coin_weight750) as coin_weight750'),

// total balance
        DB::raw("(select SUM(sell_price * inventory) from ($remittances_recipient_sql) as fff where fff.user_id=results.user_id limit 1) as total_balance"),

// sum total balance
    ])
    ->when(!empty($filters['custom_order']), function ($q) use ($filters) {
        $product_id = $filters['custom_order'][0];
        $sortOrder = $filters['custom_order'][1];
        if ($product_id == -1)
            $q->orderBy('total_balance', $sortOrder);
        else
            $q->orderByRaw("FIELD($product_id,results.id) desc")
                ->orderBy('inventory', $sortOrder);
    })
    ->when(!empty($filters['users_id_in']), function ($q) use ($filters) {
        $q->whereIn('user_id', $filters['users_id_in']);
    })
    ->get();

//        $this->sum_total($users);
if (!$paginate)
    return $users;
return $users->groupBy('user_id')->paginate($paginate);