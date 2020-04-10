<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

//use Symfony\Component\Validator\Constraints\Callback;

class FormController extends AbstractController
{
    const CUSTOMERS = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D'];
    const GAMES     = [1 => 'G1', 2 => 'G2'];

    /**
     * @Route("/form", name="form")
     */
    public function index(Request $request)
    {
        $formData = $this->createFormData();
        $customers = self::CUSTOMERS;
        $games = self::GAMES;

        $form = $this->createFormBuilder($formData, [
            'constraints' => [
                new Callback([$this, 'validateCustomerSubscription']),
            ],
        ])
            ->add('customers', CollectionType::class, [
                'entry_type'    => CheckboxType::class,
                'entry_options' => [
                    'required' => false,
                ],
            ])
            // you cannot add a game if customer is not checked
            ->add('games', CollectionType::class, [
                'entry_type'    => CollectionType::class,
                'entry_options' => [
                    'entry_type'    => CheckboxType::class,
                    'entry_options' => [
                        'required' => false,
                        'constraints' => [
//                            new EqualTo(false)
                        ]
                    ],
                ],
            ])
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            foreach ($customers as $customerId => $customer) {
                $hasSubscriptions = $data['customers'][$customerId] == true;
                if ($hasSubscriptions) $customerSubscriptions[] = $customer;
                foreach ($games as $gameId => $game) {
                    if ($data['games'][$customerId][$gameId]) {
                        if (!$hasSubscriptions) {
                            $field = $form->get('games')->get($customerId)->get($gameId);
                            $field->addError(new FormError('Customer subscription required'));
                        }
                    }
                }
            }
        }

        return $this->render('form/index.html.twig', [
            'form'      => $form->createView(),
            'data'      => $formData,
            'customers' => $customers,
            'games'     => $games,
        ]);
    }

    public function validateCustomerSubscription($data, ExecutionContextInterface $context, $payload)
    {
        foreach (self::CUSTOMERS as $customerId => $customer) {
            $hasSubscriptions = $data['customers'][$customerId] == true;
            if ($hasSubscriptions) $customerSubscriptions[] = $customer;
            foreach (self::GAMES as $gameId => $game) {
                if ($data['games'][$customerId][$gameId]) {
                    if (!$hasSubscriptions) {

                        $context
                            ->buildViolation('Customer subscription required')
                            ->atPath("[games][$customerId][$gameId]")
                            ->addViolation();
                    }
                }
            }
        }
    }

    protected function createFormData()
    {
        return [
            // [customer.id] => customer.hasSubscription(game.id)
            'customers' => [
                1 /* customer A */ => true,
                2 /* customer B */ => true,
                3 /* customer C */ => false,
                4 /* customer D */ => false,
            ],
            'games'     => [
                // [customer.id][game.id] => customer.hasSubscription(game.id)
                1 /* customer A */ => [
                    1 /* game G1 */ => true,
                    2 /* game G2 */ => true,
                ],
                2 /* customer B */ => [
                    1 /* game G1 */ => true,
                    2 /* game G2 */ => false,
                ],
                3 /* customer C */ => [
                    1 /* game G1 */ => false,
                    2 /* game G2 */ => true,
                ],
                4 /* customer D */ => [
                    1 /* game G1 */ => false,
                    2 /* game G2 */ => false,
                ],

            ],
        ];
    }

}
