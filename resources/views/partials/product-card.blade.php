<div class="product-card">
    <a href="{{ $product->url() }}" class="thumb">
        @if ($img = $product->mainImage())
            <img src="{{ $img }}" alt="{{ $product->name }}" loading="lazy" width="600" height="600">
            @if ($hover = $product->hoverImage())
                <img src="{{ $hover }}" alt="" class="hover-img" loading="lazy" width="600" height="600">
            @endif
        @else
            <span class="no-img">{{ icon('image') }}</span>
        @endif
        <span class="labels">
            @if (! $product->in_stock)<span class="label out">Tükendi</span>
            @elseif ($product->onSale())<span class="label onsale">-{{ round((1 - $product->finalPrice() / (float) $product->price) * 100) }}%</span>@endif
        </span>
    </a>
    <div class="card-actions">
        <button type="button" data-wishlist="{{ $product->id }}" @class(['in-list' => in_array($product->id, $wishlistIds ?? [], true)]) aria-label="Beğendiklerime ekle">{{ icon('heart') }}</button>
    </div>
    <h3 class="product-title"><a href="{{ $product->url() }}">{{ $product->name }}</a></h3>
    @if ($product->finalPrice() > 0)
        <span class="price">
            @if ($product->onSale())<del>{{ money($product->price) }}</del><ins>{{ money($product->finalPrice()) }}</ins>@else{{ money($product->price) }}@endif
        </span>
    @endif
    @if ($product->purchasable())
        <button type="button" class="btn-add" data-add-to-cart="{{ $product->id }}">{{ icon('cart') }} Sepete Ekle</button>
    @else
        <a href="{{ $product->url() }}" class="btn-add">{{ icon('eye') }} Devamını oku</a>
    @endif
</div>
