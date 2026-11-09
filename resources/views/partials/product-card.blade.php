@php($inList = in_array($product->id, $wishlistIds ?? [], true))
<article class="product-card pcard">
    <div class="pcard-media">
        <a href="{{ $product->url() }}" class="pcard-img" tabindex="-1" aria-hidden="true">
            @if ($img = $product->mainImage())
                <img src="{{ $img }}" alt="" loading="lazy" width="600" height="600">
                @if ($hover = $product->hoverImage())
                    <img src="{{ $hover }}" alt="" class="hover-img" loading="lazy" width="600" height="600">
                @endif
            @else
                <span class="no-img">{{ icon('image') }}</span>
            @endif
        </a>
        <span class="labels">
            @if (! $product->in_stock)<span class="label out">Tükendi</span>
            @elseif ($product->onSale())<span class="label onsale">-{{ round((1 - $product->finalPrice() / (float) $product->price) * 100) }}%</span>@endif
            @if ($product->in_stock && ($badge = $product->campaign()['badge'] ?? null))<span class="label campaign">{{ $badge }}</span>@endif
        </span>
        <button type="button" class="pcard-fav" data-wishlist="{{ $product->id }}" @class(['in-list' => $inList]) aria-label="Beğendiklerime ekle">{{ icon('heart') }}</button>
        @if ($product->purchasable())
            <button type="button" class="pcard-add" data-add-to-cart="{{ $product->id }}" aria-label="{{ $product->name }} sepete ekle">{{ icon('cart') }}<span>Sepete ekle</span></button>
        @endif
    </div>
    <div class="pcard-body">
        <h3 class="pcard-name"><a href="{{ $product->url() }}">{{ $product->name }}</a></h3>
        @if ($product->finalPrice() > 0)
            <span class="pcard-price">
                @if ($product->onSale())<ins>{{ money($product->finalPrice()) }}</ins><del>{{ money($product->price) }}</del>@else{{ money($product->price) }}@endif
            </span>
        @else
            <a href="{{ $product->url() }}" class="pcard-ask">Fiyat için bilgi alın</a>
        @endif
    </div>
</article>
