<?php
namespace App\Security;

use App\Entity\Token;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class TokenAuthenticator
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
        $this->logger->info('TokenAuthenticator constructed');
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        $this->logger->info('TokenAuthenticator triggered', ['path' => $request->getPathInfo()]);

        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            $event->setResponse(new JsonResponse(['status'=>'error','message'=>'Missing token'],401));
            return;
        }

        $tokenValue = substr($authHeader, 7);

        $token = $this->em->getRepository(Token::class)->findOneBy(['value' => trim($tokenValue)]);

        if (!$token || $token->getExpiresAt() < new \DateTimeImmutable()) {
            $event->setResponse(new JsonResponse(['status'=>'error','message'=>'Missing or invalid token'],401));
            return;
        }

        $request->attributes->set('user', $token->getAppUser());

        $this->logger->info('TokenAuthenticator success', [
            'email' => $token->getAppUser()->getEmail(),
            'roles' => $token->getAppUser()->getRoles()
        ]);
    }
}
