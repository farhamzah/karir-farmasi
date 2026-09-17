<?php

namespace App\Services;

use App\Contracts\CoreDirectoryGateway;
use App\Data\CoreDirectoryPerson;
use App\Data\CoreStudyProgram;
use App\Exceptions\CoreDirectoryUnavailable;

final class UnavailableCoreDirectoryGateway implements CoreDirectoryGateway
{
    public function findPerson(string $coreUserId): CoreDirectoryPerson
    {
        throw new CoreDirectoryUnavailable('Core directory is not configured for this environment.');
    }

    public function findStudyProgram(string $programId): CoreStudyProgram
    {
        throw new CoreDirectoryUnavailable('Core directory is not configured for this environment.');
    }

    public function listScopedPrograms(): array
    {
        throw new CoreDirectoryUnavailable('Core directory is not configured for this environment.');
    }
}
