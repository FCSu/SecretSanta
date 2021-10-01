<?php

declare(strict_types=1);

namespace App\Controller\Party;

use App\Form\Handler\PartyFormHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Party;
use App\Form\Type\PartyType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use GeoIp2\Database\Reader;

class PartyController extends AbstractController
{
    public function __construct(
        private string $geoIpDbPath,
    ) {
    }

    /**
     * @Route("/party/create", name="create_party")
     * @Template("Party/create.html.twig")
     */
    public function createAction(Request $request, PartyFormHandler $handler)
    {
        if ($request->getMethod() != 'POST') {
            return $this->redirectToRoute('homepage');
        }

        return $this->handlePartyCreation($request, new Party(), $handler);
    }

    /**
     * @Route("/created/{listurl}", name="party_created", methods={"GET"})
     * @Template("Party/created.html.twig")
     */
    public function createdAction(Party $party)
    {
        return [
            'party' => $party,
        ];
    }

    /**
     * @Route("/reuse/{listurl}", name="party_reuse", methods={"GET"})
     * @Template("Party/create.html.twig")
     */
    public function reuseAction(Request $request, Party $party, PartyFormHandler $handler)
    {
        $originalAmountOfParticipants = $party->getParticipants()->count();
        list($party, $countHashed) = $party->createNewPartyForReuse();

        $data = $this->handlePartyCreation($request, $party, $handler);
        $data['countHashed'] = $countHashed;
        $data['originalAmountOfParticipants'] = $originalAmountOfParticipants;

        return $data;
    }

    /**
     * @Route("/delete/{listurl}", name="party_delete", methods={"POST"})
     * @Template("Party/deleted.html.twig")
     */
    public function deleteAction(Request $request, Party $party, TranslatorInterface $translator)
    {
        $correctCsrfToken = $this->isCsrfTokenValid('delete_party', $request->get('csrf_token'));
        $correctConfirmation = (strtolower($request->get('confirmation')) === strtolower($translator->trans('party_manage_valid.delete.phrase_to_type')));

        if ($correctConfirmation === false || $correctCsrfToken === false) {
            $this->addFlash(
                'error',
                $translator->trans('flashes.party.not_deleted')
            );

            return $this->redirectToRoute('party_manage', ['listurl' => $party->getListurl()]);
        }

        $this->getDoctrine()->getManager()->remove($party);
        $this->getDoctrine()->getManager()->flush();
    }

    /**
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function handlePartyCreation(Request $request, Party $party, PartyFormHandler $handler)
    {
        $form = $this->createForm(PartyType::class, $party, [
            'action' => $this->generateUrl('create_party'),
        ]);

        if ($handler->handle($form, $request)) {
            return $this->redirectToRoute('party_created', ['listurl' => $party->getListurl()]);
        }

        $geoCountry = '';
        $reader = new Reader($this->geoIpDbPath);
        try {
            $geoInformation = $reader->country($request->getClientIp());
            $geoCountry = $geoInformation->country->isoCode;
        } catch (\Exception) {}

        return [
            'form' => $form->createView(),
            'geoCountry' => $geoCountry,
        ];
    }
}
