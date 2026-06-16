<?php

declare(strict_types=1);

namespace App\Features\Survey\Detail;

use App\Repositories\AuthRepository;
use App\Repositories\PrivilegeRepository;
use App\Services\JwtService;
use RuntimeException;

final class DetailSurveyService
{
    private AuthRepository $authRepository;
    private PrivilegeRepository $privilegeRepository;
    private DetailSurveyRepository $repository;

    public function __construct()
    {
        $this->authRepository = new AuthRepository();
        $this->privilegeRepository = new PrivilegeRepository();
        $this->repository = new DetailSurveyRepository();
    }

    public function getDetail(int $surveyId): array
    {
        $survey = $this->repository->getSurveyById($surveyId);
        $access = $this->getAccessContext();

        if (!$survey) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        if (
            !$access['can_manage']
            && !$this->repository->canAccessSurvey($surveyId, $access['position'])
        ) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $survey['restrictions'] = $this->repository->getRestrictionsBySurveyId($surveyId);
        $survey['pages'] = $this->repository->getPagesWithQuestionsBySurveyId($surveyId);

        return $survey;
    }

    /**
     * @return array{can_manage: bool, position: ?string}
     */
    private function getAccessContext(): array
    {
        $token = JwtService::token();

        if ($token === null) {
            return [
                'can_manage' => false,
                'position' => null,
            ];
        }

        $payload = JwtService::verify($token);

        if ($payload === null || !isset($payload->data->userId)) {
            throw new RuntimeException('Token tidak valid atau sudah kedaluwarsa', 401);
        }

        if ($this->authRepository->isTokenRevoked($token)) {
            throw new RuntimeException('Token sudah tidak berlaku', 401);
        }

        $user = $this->authRepository->findById((int) $payload->data->userId);

        if (!$user) {
            throw new RuntimeException('User tidak ditemukan', 401);
        }

        $effectiveRoleId = (bool) $user['is_active'] || $user['role'] === 'user'
            ? (int) $user['role_id']
            : (int) $user['default_role_id'];

        return [
            'can_manage' => $this->privilegeRepository->hasPrivilege(
                $effectiveRoleId,
                'survey:read'
            ),
            'position' => $user['position'] ?? null,
        ];
    }
}
