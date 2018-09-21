<?php declare(strict_types=1);

namespace Eventsourcing;

class Checkout
{
    /**
     * @var EventLog
     */
    private $eventLog;

    /**
     * @var EmitterId
     */
    private $emitterId;

    /**
     * @var CartItemCollection
     */
    private $cartItems;

    /**
     * @var BillingAddress
     */
    private $billingAddress;

    /**
     * @var bool
     */
    private $ordered = false;

    public function __construct(EventLog $history)
    {
        $this->replay($history);
        $this->eventLog = new EventLog();
    }

    /**
     * @throws CheckoutAlreadyStartedException
     * @throws \Exception
     */
    public function startCheckout(CartItemCollection $cartItems): void
    {
        if ($this->hasBeenStarted()) {
            throw new CheckoutAlreadyStartedException();
        }
        $event = new CheckoutStartedEvent(new EmitterId(), $cartItems, new \DateTimeImmutable());
        $this->recordEvent($event);
    }

    /**
     * @throws AlreadyOrderedException
     * @throws CheckoutNotStartedException
     * @throws \Exception
     */
    public function setBillingAddress(BillingAddress $billingAddress): void
    {
        if (!$this->hasBeenStarted()) {
            throw new CheckoutNotStartedException();
        }

        if ($this->ordered) {
            throw new AlreadyOrderedException();
        }

        $this->recordEvent(new BillingAddressEnteredEvent(new \DateTimeImmutable(), $this->emitterId, $billingAddress));
    }

    /**
     * @throws AlreadyOrderedException
     * @throws CheckoutNotStartedException
     * @throws MissingBillingAddressException
     * @throws \Exception
     */
    public function placeOrder(): void
    {
        if (!$this->hasBeenStarted()) {
            throw new CheckoutNotStartedException();
        }

        if ($this->billingAddress === null) {
            throw new MissingBillingAddressException();
        }

        if ($this->ordered) {
            throw new AlreadyOrderedException();
        }

        $generator = new OrderGenerator();
        $order = $generator->generate($this->billingAddress, $this->cartItems);

        $this->recordEvent(new OrderPlacedEvent(new \DateTimeImmutable(), $this->emitterId, $order));
    }

    public function getRecordedEvents(): EventLog
    {
        return $this->eventLog;
    }

    private function hasBeenStarted(): bool
    {
        return $this->emitterId !== null;
    }

    private function replay(EventLog $eventLog): void
    {
        foreach ($eventLog as $event) {
            $this->applyEvent($event);
        }
    }

    private function recordEvent(Event $event): void
    {
        $this->eventLog->append($event);
        $this->applyEvent($event);
    }

    private function applyEvent(Event $event): void
    {
        $topic = $event->getTopic();
        switch (true) {
            case $topic->equals(new CheckoutStartedTopic()):
                /** @var CheckoutStartedEvent $event */
                $this->applyCheckoutStartedEvent($event);
                break;
            case $topic->equals(new BillingAddressEnteredTopic()):
                /** @var BillingAddressEnteredEvent $event */
                $this->applyBillingAddressEnteredEvent($event);
                break;
            case $topic->equals(new OrderPlacedTopic()):
                $this->applyOrderPlacedEvent();
                break;
        }
    }

    private function applyOrderPlacedEvent(): void
    {
        $this->ordered = true;
    }

    private function applyBillingAddressEnteredEvent(BillingAddressEnteredEvent $event): void
    {
        $this->billingAddress = $event->getBillingAddress();
    }

    private function applyCheckoutStartedEvent(CheckoutStartedEvent $event): void
    {
        $this->emitterId = $event->getEmitterId();
        $this->cartItems = $event->getCartItems();
    }
}
