<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Performance Reviews</h1>
            <p class="text-sm text-slate-500">Visible only to HR and the reviewed employee.</p>
        </div>

        @can('hr.manage')
            <button wire:click="create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                New review
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-2">
            <div>
                <x-input-label value="Employee" />
                <select wire:model="employeeId" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">Select…</option>
                    @foreach ($this->employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->fullName() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('employeeId')" />
            </div>
            <div>
                <x-input-label value="Review period" />
                <x-text-input type="text" wire:model="reviewPeriod" />
                <x-input-error :messages="$errors->get('reviewPeriod')" />
            </div>
            <div>
                <x-input-label value="Review date" />
                <x-text-input type="date" wire:model="reviewDate" />
                <x-input-error :messages="$errors->get('reviewDate')" />
            </div>
            <div>
                <x-input-label value="Rating" />
                <select wire:model="rating" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($ratings as $r)
                        <option value="{{ $r->value }}">{{ ucfirst(str_replace('_', ' ', $r->value)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-full">
                <x-input-label value="Strengths" />
                <textarea wire:model="strengths" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"></textarea>
            </div>
            <div class="col-span-full">
                <x-input-label value="Areas for improvement" />
                <textarea wire:model="areasForImprovement" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"></textarea>
            </div>
            <div class="col-span-full">
                <x-input-label value="Comments" />
                <textarea wire:model="comments" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"></textarea>
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="space-y-3">
        @forelse ($this->reviews as $review)
            <div wire:key="review-{{ $review->id }}" class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-slate-800">{{ $review->employee->fullName() }} — {{ $review->review_period }}</p>
                        <p class="text-sm text-slate-500">Reviewed by {{ $review->reviewer->name }} on {{ $review->review_date->format('M j, Y') }}</p>
                    </div>
                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                        {{ ucfirst(str_replace('_', ' ', $review->rating->value)) }}
                    </span>
                </div>

                @if ($review->strengths)
                    <p class="mt-2 text-sm text-slate-600"><span class="font-medium">Strengths:</span> {{ $review->strengths }}</p>
                @endif
                @if ($review->areas_for_improvement)
                    <p class="mt-1 text-sm text-slate-600"><span class="font-medium">Areas for improvement:</span> {{ $review->areas_for_improvement }}</p>
                @endif
                @if ($review->comments)
                    <p class="mt-1 text-sm text-slate-600"><span class="font-medium">Comments:</span> {{ $review->comments }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-500">No performance reviews yet.</p>
        @endforelse
    </div>
</div>
