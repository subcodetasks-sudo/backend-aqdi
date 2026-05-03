 
<div class="container">
    <center>
        <h1 style="color: rgb(35, 179, 129)">تعرف على عقدي</h1>
        <br><br>
      </center>
    <div class="row">
        @foreach ($videos as $video)
            @php
                $parsedUrl = parse_url($video->url);
                $embedUrl = '';

                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    if (isset($queryParams['v'])) {
                        $embedUrl = 'https://www.youtube.com/embed/' . $queryParams['v'];
                    }
                }

                if (empty($embedUrl) && isset($parsedUrl['path'])) {
                    $videoId = trim($parsedUrl['path'], '/');
                    $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
                }
            @endphp

            @if (!empty($embedUrl))
                <div class="col-md-3 mb-2">
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="{{ $embedUrl }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <b class="mt-2" style="color: rgb(55, 169, 165)">{{ $video->desc }}</b>
                </div>
            @else
                <div class="col-md-6 mb-4">
                    <p class="text-danger">Unable to embed video from URL: {{ $video->url }}</p>
                </div>
            @endif

        @endforeach
    </div>

    <div class="mt-4">
        {{ $videos->links('pagination::bootstrap-4') }}
    </div>
</div>
 
