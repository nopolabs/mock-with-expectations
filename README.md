# MockWithExpectationsTrait

[![Build Status](https://travis-ci.org/nopolabs/mock-with-expectations.svg?branch=master)](https://travis-ci.org/nopolabs/mock-with-expectations)
[![License](https://poser.pugx.org/nopolabs/mock-with-expectations/license)](https://packagist.org/packages/nopolabs/mock-with-expectations)

This trait contains some helper methods to be used in a sub-class of
`PHPUnit\Framework\TestCase`.

I like testing with mocks and expectations.
It lets me test my code without having to test the components with which it interacts.
Those components need to be tested too, but not by each test I write of code that uses them.

Consider this `refundOrder()` function:
```
public function refundOrder($orderId)
{
    $order = $this->orderRepository->findOrder($orderId);
    if ($order->isRefundable()) {
        $this->orderRefunder->refund($order);
    }
}
```

Here is a PHPUnit test for `refundOrder()`:
```
public function testRefundOrder()
{
    $orderId = 1337;
    $order = $this->createMock(Order::class);
    $orderRepository = $this->createMock(OrderRepository::class);
    $orderRefunder = $this->createMock(OrderRefunder::class);
    
    $orderRepository->expects($this->once())
        ->method('findOrder')
        ->with($orderId)
        ->willReturn($order);
     
    $order->expects($this->once())
        ->method('isRefundable')
        ->willReturn(true);
        
    $orderRefunder->expects($this->once())
        ->method('refund')
        ->with($order);
        
    $manager = new OrderManager($orderRepository, $orderRefunder);
    
    $manager->refundOrder($orderId);
}
```

Refactoring `refundOrder()`:

```
public function refundOrder($orderId)
{
    $order = $this->findOrder($orderId);
    if ($this->isRefundable($order)) {
        $this->refund($order);
    }
}

protected function findOrder($orderId) : Order
{
    return $this->orderRepository->findOrder($orderId);
}

protected function isRefundable(Order $order) : bool
{
    return $order->isRefundable();
}

protected function refund(Order $order)
{
    return $this->orderRefunder->refund($order);
}
```

And using `MockWithExpectationsTrait`:
```
public function testRefundOrder()
{
    $orderId = 1337;
    $order = $this->createMock(Order::class);
    $manager = $this->newPartialMockWithExpectations(OrderManager::class, [
        ['findOrder', ['params' => [$orderId], 'result' => $order],
        ['isRefundable', ['params' => [$order], 'result' => true]
        ['refund', ['params' => [$order]]],
    ]);
    $manager->refundOrder($orderId);
}

public function testRefundOrderNotRefundable()
{
    $orderId = 1337;
    $order = $this->createMock(Order::class);
    $manager = $this->newPartialMockWithExpectations(OrderManager::class, [
        ['findOrder', ['params' => [$orderId], 'result' => $order],
        ['isRefundable', ['params' => [$order], 'result' => false]
        ['refund', 'never'],
    ]);
    $manager->refundOrder($orderId);
}
```

Using `MockWithExpectationsTrait` reduces the amount of boilerplate code
needed to write the tests. In addition it is using the `at()` invocation
matcher to ensure that the methods are called in the expected order. The
original test does not check the order in which the methods are called.

The way I look at it is this:
`testRefundOrder()` is testing an external API exposed by `OrderManager`.
In turn `OrderManager` composes functions method calls to several objects
to implement the `refundOrder()` function. The refactoring organizes these
calls as a single internal API. In this example the internal API is
implemented as three protected methods on `OrderManager`. These methods
are tools that help `OrderManager` to do its job of order management.
As the internal order management API grows it might get moved to its
own `OrderManagement` class, e.g.:

```
class OrderManagement
{
    private $orderRepository;
    private $orderRefunder;
    
    public __construct(OrderRepository $orderRepository, OrderRefunder $orderRefunder)
    {
        $this->orderRepository = $orderRepository;
        $this->orderRefunder = $orderRefunder;
    }
    
    public function findOrder($orderId) : Order
    {
        return $this->orderRepository->findOrder($orderId);
    }
    
    public function isRefundable(Order $order) : bool
    {
        return $order->isRefundable();
    }
    
    public function refund(Order $order)
    {
        return $this->orderRefunder->refund($order);
    }
}

class OrderManager
{
    private $orderManagement;
    
    public __construct(OrderManagement $orderManagement)
    {
        $this->orderManagement = $orderManagement;
    }
    
    public function refundOrder($orderId)
    {
        $order = $this->orderManagement->findOrder($orderId);
        if ($order->orderManagement->isRefundable()) {
            $this->orderManagement->refund($order);
        }
    }
}

class OrderManagerTest extends TestCase
{
    use MockWithExpectationsTrait;
    
    public function testRefundOrder()
    {
        $orderId = 1337;
        $order = $this->createMock(Order::class);
        $management = $this->newPartialMockWithExpectations(OrderManagement::class, [
            ['findOrder', ['params' => [$orderId], 'result' => $order],
            ['isRefundable', ['params' => [$order], 'result' => true]
            ['refund', ['params' => [$order]]],
        ]);
        $manager = new OrderManager($management);
        
        $manager->refundOrder($orderId);
    }
}
```

# Using MockWithExpectationsTrait

## composer require

    composer require nopolabs/mock-with-expectations

## methods

### newPartialMockWithExpectations

### newPartialMock

### setExpectations

### setAtExpectations

### setExpectation

### convertToMatcher
