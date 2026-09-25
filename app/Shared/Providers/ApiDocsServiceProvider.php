<?php

namespace App\Shared\Providers;

use App\Models\User;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * OpenAPI documentation generated from the code by Scramble (FormRequests, Resources, PHPDoc):
 *   - /docs/api        Scramble UI (Stoplight Elements)
 *   - /docs/swagger    Swagger UI on the same specification
 *   - /docs/api.json   raw OpenAPI 3.1 document
 * Open in local env; elsewhere only when API_DOCS_PUBLIC=true.
 */
class ApiDocsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('viewApiDocs', fn (?User $user = null) => (bool) config('moncolis.docs_public'));

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->components->addSecurityScheme(
                    'bearerAuth',
                    SecurityScheme::http('bearer')->setDescription('Jeton renvoyé par /login_check ou /register.'),
                );
                $openApi->components->addSecurityScheme(
                    'clientAppCode',
                    SecurityScheme::apiKey('header', 'client-app-code')
                        ->setDescription('Code de l\'application cliente (vérifié si MONCOLIS_CLIENT_APP_CODES est renseigné).'),
                );
                $openApi->security = [new SecurityRequirement(['bearerAuth' => [], 'clientAppCode' => []])];
            })
            ->withOperationTransformers(function (Operation $operation) {
                // Public routes (@unauthenticated) still need the client-app-code header.
                if ($operation->security === []) {
                    $operation->security = [new SecurityRequirement(['clientAppCode' => []])];
                }
            });

        Route::get('/docs/swagger', fn () => view('api-docs.swagger', [
            'specUrl' => url('/docs/api.json'),
            'title' => config('scramble.ui.title'),
        ]))->middleware(['web', RestrictedDocsAccess::class])->name('docs.swagger');
    }
}
