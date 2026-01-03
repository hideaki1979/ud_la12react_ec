<p>管理者様</p>
<p>新しい注文がありました。</p>
<h3>注文者情報</h3>
<p>名前： {{ $user->name }}</p>
<p>メール： {{ $user->email }}</p>
<p>配送先： 〒{{ $user->zipcode }} {{ $user->address }}</p>
<h3>注文内容</h3>
<ul>
    @foreach ($cart as $item)
        <li>
            {{ $item['name'] }} - {{ $item['quantity'] }}個 - ¥{{ number_format($item['price']) }}
        </li>
    @endforeach
</ul>

<p>合計金額： ¥{{ number_format($totalPrice) }}</p>
