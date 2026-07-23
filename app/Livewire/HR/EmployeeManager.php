<?php

declare(strict_types=1);

namespace App\Livewire\HR;

use App\Domain\HR\Enums\EmployeeStatus;
use App\Domain\HR\Enums\EmploymentType;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Employees')]
class EmployeeManager extends Component
{
    use InteractsWithActiveBranch;
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingEmployeeId = null;

    public string $firstName = '';

    public string $lastName = '';

    public string $department = '';

    public string $jobTitle = '';

    public string $employmentType = 'full_time';

    public string $hireDate = '';

    public string $baseSalary = '';

    public string $email = '';

    public string $phone = '';

    public string $search = '';

    protected function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'jobTitle' => ['required', 'string', 'max:255'],
            'employmentType' => ['required', 'string'],
            'hireDate' => ['required', 'date'],
            'baseSalary' => ['required', 'numeric', 'min:0'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function employees(): LengthAwarePaginator
    {
        return Employee::where('branch_id', $this->branchId)
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('employee_number', 'like', "%{$this->search}%")))
            ->orderBy('last_name')
            ->paginate(15);
    }

    public function create(): void
    {
        $this->authorize('create', Employee::class);

        $this->reset(['firstName', 'lastName', 'department', 'jobTitle', 'hireDate', 'baseSalary', 'email', 'phone', 'editingEmployeeId']);
        $this->employmentType = 'full_time';
        $this->hireDate = now()->toDateString();
        $this->showForm = true;
    }

    public function edit(int $employeeId): void
    {
        $employee = Employee::findOrFail($employeeId);
        $this->authorize('update', $employee);

        $this->editingEmployeeId = $employee->id;
        $this->firstName = $employee->first_name;
        $this->lastName = $employee->last_name;
        $this->department = $employee->department;
        $this->jobTitle = $employee->job_title;
        $this->employmentType = $employee->employment_type->value;
        $this->hireDate = $employee->hire_date->toDateString();
        $this->baseSalary = number_format($employee->base_salary_cents / 100, 2, '.', '');
        $this->email = (string) $employee->email;
        $this->phone = (string) $employee->phone;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'branch_id' => $this->branchId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'department' => $this->department,
            'job_title' => $this->jobTitle,
            'employment_type' => $this->employmentType,
            'hire_date' => $this->hireDate,
            'base_salary_cents' => (int) round(((float) $this->baseSalary) * 100),
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
        ];

        if ($this->editingEmployeeId) {
            $employee = Employee::findOrFail($this->editingEmployeeId);
            $this->authorize('update', $employee);
            $employee->update($data);
        } else {
            $this->authorize('create', Employee::class);
            $data['employee_number'] = 'EMP-' . now()->format('y') . '-' . str_pad((string) (Employee::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
            $data['status'] = EmployeeStatus::Active;
            Employee::create($data);
        }

        $this->showForm = false;
    }

    public function terminate(int $employeeId): void
    {
        $employee = Employee::findOrFail($employeeId);
        $this->authorize('update', $employee);

        $employee->update(['status' => EmployeeStatus::Terminated, 'termination_date' => now()->toDateString()]);
    }

    public function render()
    {
        return view('livewire.hr.employee-manager', [
            'employmentTypes' => EmploymentType::cases(),
            'employees' => $this->employees(),
        ]);
    }
}
