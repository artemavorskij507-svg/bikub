<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Categories --}}
    @foreach($categories as $category)
        <url>
            <loc>{{ url('/category/classifieds') }}</loc>
            <lastmod>{{ $category->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach

    {{-- Published Ads --}}
    @foreach($ads as $ad)
        <url>
            <loc>{{ route('classifieds.show', $ad->slug) }}</loc>
            <lastmod>{{ $ad->updated_at->toAtomString() }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach
</urlset>

