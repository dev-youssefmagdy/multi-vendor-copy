<?php

namespace App\View\Components\Elora;

use Illuminate\View\Component;

class CategoryTabs extends Component
{
    public $category;
    public $keyword;
    public $sort;
    public $availability;
    public $productFlag;
    public $sale;
    public $ratings;
    public $min;
    public $max;

    public $pageBase;
    public $isFlashActive;
    public $isTopRatedActive;
    public $isCouponActive;
    public $isNewInActive;
    public $flashUrl;
    public $topRatedUrl;
    public $couponUrl;
    public $newInUrl;
    public $fastDeliveryUrl;

    public function __construct($category, $keyword = null, $sort = null, $availability = null, $productFlag = null, $sale = null, $ratings = null, $min = null, $max = null)
    {
        $this->category = $category;
        $this->keyword = $keyword;
        $this->sort = $sort;
        $this->availability = $availability;
        $this->productFlag = $productFlag;
        $this->sale = $sale;
        $this->ratings = $ratings;
        $this->min = $min;
        $this->max = $max;

        $this->pageBase = $this->category
            ? route('tenant.storefront.category', $this->category->slug)
            : route('tenant.storefront.category');

        // A tab is active only when its own param is set AND no other tab param is set
        $this->isFlashActive = $this->sale === 'flash_sale' && !$this->ratings && !$this->productFlag;
        $this->isTopRatedActive = $this->ratings === '4' && !$this->sale && !$this->productFlag;
        $this->isCouponActive = $this->sale === 'on_sale' && !$this->ratings && !$this->productFlag;
        $this->isNewInActive = $this->productFlag === 'featured' && !$this->sale && !$this->ratings;

        // Activating a tab clears every other tab param (single-select behaviour)
        $clearOthers = ['on_sale' => '', 'ratings' => '', 'product_flag' => ''];

        $this->flashUrl = $this->isFlashActive
            ? $this->mkUrl($clearOthers)
            : $this->mkUrl(array_merge($clearOthers, ['on_sale' => 'flash_sale']));

        $this->topRatedUrl = $this->isTopRatedActive
            ? $this->mkUrl($clearOthers)
            : $this->mkUrl(array_merge($clearOthers, ['ratings' => '4']));

        $this->couponUrl = $this->isCouponActive
            ? $this->mkUrl($clearOthers)
            : $this->mkUrl(array_merge($clearOthers, ['on_sale' => 'on_sale']));

        $this->newInUrl = $this->isNewInActive
            ? $this->mkUrl($clearOthers)
            : $this->mkUrl(array_merge($clearOthers, ['product_flag' => 'featured']));

        $this->fastDeliveryUrl = $this->mkUrl($clearOthers);
    }

    protected function mkUrl(array $extra)
    {
        $params = array_merge([
            'keyword' => $this->keyword,
            'sort' => $this->sort,
            'availability' => $this->availability,
            'product_flag' => $this->productFlag,
            'on_sale' => $this->sale,
            'ratings' => $this->ratings,
            'min' => $this->min,
            'max' => $this->max,
        ], $extra);

        $filtered = array_filter($params, function ($v) {
            return $v !== '' && $v !== null;
        });

        return $this->pageBase . (count($filtered) ? ('?' . http_build_query($filtered)) : '');
    }

    public function render()
    {
        return view('components.elora.category-tabs');
    }
}
