<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Livewire\Component;

/**
 * Architecture tests enforcing docs/architecture/coding-standards.md's
 * layering rules (Controller -> FormRequest -> Action/Service -> Model) and
 * general code hygiene. These run structurally against the codebase (no
 * database, no HTTP) so they're fast and catch drift the moment a new file
 * violates a convention, rather than at review time.
 */
arch()->preset()->php();
arch()->preset()->security();

arch('application code declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('the domain layer stays framework-view-agnostic — no Livewire or controller dependencies')
    ->expect('App\Domain')
    ->not->toUse(['Livewire', 'App\Http\Controllers']);

arch('models do not perform HTTP or controller concerns directly')
    ->expect('App\Models')
    ->not->toUse([Request::class, 'App\Http\Controllers']);

arch('models extend Eloquent\'s base Model')
    ->expect('App\Models')
    ->classes()
    ->toExtend(Model::class);

arch('policies are named consistently')
    ->expect('App\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

arch('form requests extend Laravel\'s FormRequest')
    ->expect('App\Http\Requests')
    ->classes()
    ->toExtend(FormRequest::class);

arch('controllers are named consistently')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch('livewire components live under App\Livewire and extend Livewire\Component')
    ->expect('App\Livewire')
    ->classes()
    ->toExtend(Component::class);
