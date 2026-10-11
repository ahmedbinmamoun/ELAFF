{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>{{ __('site.name') }}@if ($category) — {{ $category->category->name }}@endif</title>
        <link>{{ $category ? route('category.show', $category->category->slug) : route('home') }}</link>
        <atom:link href="{{ url()->current() }}" rel="self" type="application/rss+xml"/>
        <description>{{ $category?->description ?? __('site.tagline') }}</description>
        <language>ar</language>
        @foreach ($items as $item)
            @php($link = route('article.show', ['id' => $item->id, 'slug' => $item->slug]))
            <item>
                <title>{{ $item->title }}</title>
                <link>{{ $link }}</link>
                <guid isPermaLink="false">{{ route('article.show', ['id' => $item->id]) }}</guid>
                <pubDate>{{ $item->publishedAt->format(DATE_RSS) }}</pubDate>
                <category>{{ $item->category->name }}</category>
                @foreach ($item->authors as $author)
                    <dc:creator>{{ $author->name }}</dc:creator>
                @endforeach
                @if ($item->excerpt)
                    <description>{{ $item->excerpt }}</description>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
