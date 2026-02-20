<?php
declare(strict_types=1);

final class AssessmentsController
{
    public function __construct(
        private readonly AssessmentRepository $assessments,
        private readonly ErrorController $errors,
    ) {
    }

    public function getAllAssessments(): void
    {
        $app = app_context();
        $error = null;
        $assessments = [];

        try {
            $assessments = $this->assessments->getAllAssessments();
        } catch (Throwable $exception) {
            app_report_exception($exception);
            $error = app_is_debug()
                ? $exception->getMessage()
                : 'Terjadi kesalahan saat memuat data assessment.';
        }

        render('pages/assessments', [
            'pageTitle' => 'Assessment List - ' . $app['name'],
            'assessments' => $assessments,
            'error' => $error,
        ]);
    }

    public function getAssessmentDetailByID(string $id): void
    {
        $path = '/assessments/' . $id;

        if (!$this->isValidPositiveInteger($id)) {
            $this->errors->notFound($path, 'Assessment ID tidak valid.');
            return;
        }

        try {
            $assessment = $this->assessments->getAssessmentDetailByID((int) $id);
        } catch (Throwable $exception) {
            app_report_exception($exception);
            $this->errors->serverError($path);
            return;
        }

        if ($assessment === null) {
            $this->errors->notFound($path, 'Assessment tidak ditemukan.');
            return;
        }

        render('pages/assessment-detail', [
            'pageTitle' => 'Assessment Detail #' . $id,
            'assessment' => $assessment,
        ]);
    }

    private function isValidPositiveInteger(string $value): bool
    {
        return ctype_digit($value) && (int) $value > 0;
    }
}
