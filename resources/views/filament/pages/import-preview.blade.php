@php
    $cols = ['full_name' => 'Name', 'phone' => 'Phone', 'gender' => 'Gender', 'date_of_birth' => 'Birth date', 'school' => 'School', 'guardian_phone' => 'Guardian phone'];
    $bad = collect($rows)->filter(fn ($r) => $r['errors'])->count();
@endphp
<div class="text-sm">
    <p class="mb-3 text-gray-600 dark:text-gray-400">
        First {{ count($rows) }} rows as they will be saved.
        @if ($bad) <span class="font-semibold text-danger-600">{{ $bad }} with errors will be skipped</span> (you'll get an error report). @endif
    </p>
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/5">
                <tr>
                    <th class="px-3 py-2">Row</th>
                    @foreach ($cols as $label) <th class="px-3 py-2">{{ $label }}</th> @endforeach
                    <th class="px-3 py-2">Problems</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr @class(['border-t border-gray-100 dark:border-white/5', 'bg-danger-50 dark:bg-danger-500/10' => $r['errors']])>
                        <td class="px-3 py-2 text-gray-400">{{ $r['row'] }}</td>
                        @foreach (array_keys($cols) as $f)
                            @php $v = $r['values'][$f] ?? null; @endphp
                            <td class="px-3 py-2">{{ $v instanceof \DateTimeInterface ? $v->format('d M Y') : ($v instanceof \BackedEnum ? $v->value : $v) }}</td>
                        @endforeach
                        <td class="px-3 py-2">
                            @foreach ($r['errors'] as $e) <div class="text-danger-600">✗ {{ $e }}</div> @endforeach
                            @foreach ($r['warnings'] as $w) <div class="text-warning-600">⚠ {{ $w }}</div> @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($cols) + 2 }}" class="px-3 py-4 text-center text-gray-500">No data rows found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
