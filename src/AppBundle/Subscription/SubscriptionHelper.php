<?php

namespace AppBundle\Subscription;

use AppBundle\Entity\Subscription;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class SubscriptionHelper
{
    /** @var SubscriptionPlan[] */
    private $plans = [];

    /** @var EntityManager  */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->plans[] = new SubscriptionPlan(
            'farmer_brent_monthly',
            'Farmer Brent',
            '99'
        );
        $this->plans[] = new SubscriptionPlan(
            'new_zealander_monthly',
            'New Zealander',
            '199'
        );

        $this->em = $em;
    }

    /**
     * @param $planId
     * @return SubscriptionPlan|null
     */
    public function findPlan($planId)
    {
        foreach ($this->plans as $plan) {
            if ($plan->getPlanId() == $planId) {
                return $plan;
            }
        }
    }

    public function addSubscriptionToUser(
        \Stripe\Subscription $stripeSubscription,
        User $user
    ) {
        $subscription = $user->getSubscription();
        if (!$subscription) {
            $subscription = new Subscription();
            $subscription->setUser($user);
        }

        $periodEnd = \DateTime::createFromFormat('U', $stripeSubscription->current_period_end);

        $subscription->activateSubscription(
            $stripeSubscription->plan->id,
            $stripeSubscription->id,
            $periodEnd
        );

        $this->em->persist($subscription);
        $this->em->flush($subscription);
    }

    public function updateCardDetails(User $user, \Stripe\Customer $stripeCustomer)
    {
        $cardDetails = $stripeCustomer->sources->data[0];

        $user->setCardLast4($cardDetails->last4);
        $user->setCardBrand($cardDetails->brand);

        $this->em->persist($user);
        $this->em->flush($user);
    }
}
