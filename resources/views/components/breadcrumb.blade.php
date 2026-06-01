<!-- start page title -->
@php
$items = $crumbs ?? [];
if ($li_1) array_unshift($items, ['label' => $li_1, 'url' => $li_1_url ?? 'javascript:void(0)' ]);
// if ($li_2) array_push($items, ['label' => $li_2, 'url' => $li_2_url ?? null]);
// if ($li_3) array_push($items, ['label' => $li_3, 'url' => $li_3_url ?? null]);
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">{{ $title ?? '' }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    @foreach($items as $i => $item)
                        @if($i < count($items) - 1)
                            <li class="breadcrumb-item"><a href="{{ $item['url'] ?? 'javascript:void(0)' }}">{{ $item['label'] }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->
