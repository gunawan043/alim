@if(($kokurikulerProjects ?? collect())->isNotEmpty())
    <div class="section-title">Projek {{ $curriculumProfile?->label('kokurikuler_label', 'P5 / P5RA') }}</div>
    <div class="section-body">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th>Judul Projek</th>
                    <th style="width:150px">Tema</th>
                    <th style="width:170px">Dimensi / Nilai</th>
                    <th style="width:130px">Pelaksanaan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kokurikulerProjects as $index => $project)
                    @php
                        $projectDimensions = collect($project->dimensions ?? [])
                            ->map(fn ($key) => $curriculumProfile?->values()[$key] ?? $key)
                            ->filter()
                            ->implode(', ');
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td><strong>{{ $project->title }}</strong><br><span class="small">{{ $project->description }}</span></td>
                        <td>{{ $project->theme ?: '—' }}</td>
                        <td class="small">{{ $projectDimensions ?: '—' }}</td>
                        <td class="small">
                            {{ $project->start_date?->format('d/m/Y') ?? '—' }}
                            @if($project->end_date) – {{ $project->end_date->format('d/m/Y') }} @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
