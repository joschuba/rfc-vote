<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'reputation',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'reputation' => 'int',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function argumentVotes(): HasMany
    {
        return $this->hasMany(ArgumentVote::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function arguments(): HasMany
    {
        return $this->hasMany(Argument::class);
    }

    public function createVote(Rfc $rfc, VoteType $type): Vote
    {
        return DB::transaction(function () use ($type, $rfc) {
            $vote = $this->getVoteForRfc($rfc);

            if (! $vote) {
                $vote = new Vote([
                    'user_id' => $this->id,
                    'rfc_id' => $rfc->id,
                ]);
            }

            $vote->type = $type;

            $vote->save();

            $rfc->update([
                'count_yes' => $rfc->yesVotes()->count(),
                'count_no' => $rfc->noVotes()->count(),
            ]);

            return $vote;
        });
    }

    public function saveArgument(Rfc $rfc, string $body): Argument
    {
        $argument = $this->getArgumentForRfc($rfc);

        if (! $argument) {
            $argument = new Argument([
                'user_id' => $this->id,
                'rfc_id' => $rfc->id,
            ]);
        }

        $argument->body = $body;

        $argument->save();

        return $argument;
    }

    public function getVoteForRfc(Rfc $rfc): ?Vote
    {
        return $this->votes->first(fn (Vote $vote) => $vote->rfc_id === $rfc->id);
    }

    public function getArgumentForRfc(Rfc $rfc): ?Argument
    {
        return $this->arguments->first(fn (Argument $argument) => $argument->rfc_id === $rfc->id);
    }

    public function hasVotedForArgument(Argument $argument): bool
    {
        return $this->getArgumentVoteForArgument($argument) !== null;
    }

    public function getArgumentVoteForArgument(Argument $argument): ?ArgumentVote
    {
        return $this->argumentVotes->first(fn (ArgumentVote $argumentVote) => $argumentVote->argument_id === $argument->id);
    }

    public function toggleArgumentVote(Argument $argument): void
    {
        DB::transaction(function () use ($argument) {
            $argumentVote = $this->getArgumentVoteForArgument($argument);

            if ($argumentVote) {
                $argumentVote->delete();
            } else {
                ArgumentVote::create([
                    'argument_id' => $argument->id,
                    'user_id' => $this->id,
                ]);
            }

            $argument->update([
                'vote_count' => $argument->votes()->count(),
            ]);
        });
    }
}
