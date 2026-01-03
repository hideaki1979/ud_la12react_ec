<p>{{ $user->name }}様</p>
<p>このたびはご注文いただき誠にありがとうございます。</p>
<h3>ご注文内容</h3>
<ul>
    @foreach ($cart as $item)
        <li>
            {{ $item['name'] }} - {{ $item['quantity'] }}個 - ¥{{ number_format($item['price']) }}
        </li>
    @endforeach
</ul>

<p>合計金額： ¥{{ number_format($totalPrice) }}</p>
<p>配送先： 〒{{ $user->zipcode }} {{ $user->address }}</p>
<p>今後ともよろしくお願いいたします。</p>
