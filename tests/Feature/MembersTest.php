<?php

use App\Enums\FeeAppliesTo;
use App\Enums\FeeFrequency;
use App\Filament\Resources\MemberResource\Pages\ListMembers;
use App\Filament\Resources\StudentResource\Pages\ListStudents;
use App\Models\FeePlan;
use App\Models\Member;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\DuesGenerator;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('app.admin', ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'secret-password']);
    $this->seed(RolesSeeder::class);
    $this->actingAs(User::where('email', 'boss@example.com')->sole());
});

it('turns a student into a member with a team', function () {
    $student = Student::factory()->create();

    Livewire::test(ListStudents::class)
        ->callTableAction('makeMember', $student, ['consent' => true, 'team' => 'media'])
        ->assertHasNoTableActionErrors();

    $member = Member::sole();
    expect($member->student_id)->toBe($student->id)
        ->and($member->full_name)->toBe($student->full_name)
        ->and($member->team->value)->toBe('media')
        ->and($member->monthly_contribution_plan_id)->toBeNull();
});


it('only bills members who agreed to contribute', function () {
    $plan = FeePlan::factory()->monthlyContribution()->create();
    $giver = Member::factory()->create(['monthly_contribution_plan_id' => $plan->id, 'is_active' => true]);
    $other = Member::factory()->create(['monthly_contribution_plan_id' => null, 'is_active' => true]);

    app(DuesGenerator::class)->generateMonthly();

    expect($giver->dues()->count())->toBe(1)->and($other->dues()->count())->toBe(0);

    Livewire::test(ListMembers::class)->set('activeTab', 'contributors')
        ->assertCanSeeTableRecords([$giver])->assertCanNotSeeTableRecords([$other]);
});
