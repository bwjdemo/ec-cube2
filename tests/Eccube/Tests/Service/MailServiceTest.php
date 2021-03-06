<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Tests\Service;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\Shipping;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Service\MailService;
use Symfony\Component\HttpFoundation\Request;

/**
 * MailService test cases.
 */
class MailServiceTest extends AbstractServiceTestCase
{
    /**
     * @var Customer
     */
    protected $Customer;

    /**
     * @var Order
     */
    protected $Order;
    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Swift_Message
     */
    protected $Message;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
        $this->Order = $this->createOrder($this->Customer);
        $this->BaseInfo = $this->entityManager->find(BaseInfo::class, 1);
        $this->mailService = $this->container->get(MailService::class);

        $request = Request::createFromGlobals();
        $this->container->get('request_stack')->push($request);
        $twig = $this->container->get('twig');
        $twig->addGlobal('BaseInfo', $this->BaseInfo);
    }

    public function testSendCustomerConfirmMail()
    {
        $url = 'http://example.com/confirm';
        $this->mailService->sendCustomerConfirmMail($this->Customer, $url);

        $mailCollector = $this->getMailCollector();
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        /** @var \Swift_Message $Message */
        $Message = $collectedMessages[0];

        $this->expected = $url;
        $this->verifyRegExp($Message, 'URL???'.$url.'?????????????????????');

        $this->expected = '['.$this->BaseInfo->getShopName().'] ????????????????????????';
        $this->actual = $Message->getSubject();
        $this->verify();

        $this->expected = $this->Customer->getEmail();
        $this->actual = key($Message->getTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail03();
        $this->actual = key($Message->getReplyTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail01();
        $this->actual = key($Message->getBcc());
        $this->verify();

        // HTML??????????????????
        $this->assertEquals(1, count($Message->getChildren()));
    }

    public function testSendCustomerCompleteMail()
    {
        $this->mailService->sendCustomerCompleteMail($this->Customer);

        $mailCollector = $this->getMailCollector();
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        /** @var \Swift_Message $Message */
        $Message = $collectedMessages[0];

        $this->expected = '['.$this->BaseInfo->getShopName().'] ????????????????????????????????????';
        $this->actual = $Message->getSubject();
        $this->verify();

        $this->expected = $this->Customer->getEmail();
        $this->actual = key($Message->getTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail03();
        $this->actual = key($Message->getReplyTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail01();
        $this->actual = key($Message->getBcc());
        $this->verify();

        // HTML??????????????????
        $this->assertEquals(1, count($Message->getChildren()));
    }

    public function testSendCustomerWithdrawMail()
    {
        $email = 'draw@example.com';
        $this->mailService->sendCustomerWithdrawMail($this->Customer, $email);

        $mailCollector = $this->getMailCollector();
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        /** @var \Swift_Message $Message */
        $Message = $collectedMessages[0];

        $this->expected = '['.$this->BaseInfo->getShopName().'] ???????????????????????????';
        $this->actual = $Message->getSubject();
        $this->verify();

        $this->expected = $email;
        $this->actual = key($Message->getTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail03();
        $this->actual = key($Message->getReplyTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail01();
        $this->actual = key($Message->getBcc());
        $this->verify();

        // HTML??????????????????
        $this->assertEquals(0, count($Message->getChildren()));
    }

    public function testSendContactMail()
    {
        $faker = $this->getFaker();
        $name01 = $faker->lastName;
        $name02 = $faker->firstName;
        $kana01 = $faker->lastName;
        $kana02 = $faker->firstName;
        $email = $faker->email;
        $postCode = $faker->postCode;
        $Pref = $this->entityManager->find(\Eccube\Entity\Master\Pref::class, 1);
        $addr01 = $faker->city;
        $addr02 = $faker->streetAddress;

        $formData = [
            'name01' => $name01,
            'name02' => $name02,
            'kana01' => $kana01,
            'kana02' => $kana02,
            'postal_code' => $postCode,
            'pref' => $Pref,
            'addr01' => $addr01,
            'addr02' => $addr02,
            'phone_number' => $faker->phoneNumber,
            'email' => $email,
            'contents' => '????????????????????????',
        ];

        $this->mailService->sendContactMail($formData);

        $mailCollector = $this->getMailCollector();
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        /** @var \Swift_Message $Message */
        $Message = $collectedMessages[0];

        $this->expected = '['.$this->BaseInfo->getShopName().'] ?????????????????????????????????????????????';
        $this->actual = $Message->getSubject();
        $this->verify();

        $this->expected = $email;
        $this->actual = key($Message->getTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail03();
        $this->actual = key($Message->getReplyTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail01();
        $this->actual = key($Message->getBcc());

        $this->expected = '????????????????????????';
        $this->verifyRegExp($Message, '????????????????????????');

        // HTML??????????????????
        $this->assertEquals(1, count($Message->getChildren()));
    }

    public function testSendOrderMail()
    {
        $Order = $this->Order;
        $this->mailService->sendOrderMail($Order);

        $mailCollector = $this->getMailCollector();
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        /** @var \Swift_Message $Message */
        $Message = $collectedMessages[0];

        $this->expected = '['.$this->BaseInfo->getShopName().'] ???????????????????????????????????????';
        $this->actual = $Message->getSubject();
        $this->verify();

        $this->expected = $Order->getEmail();
        $this->actual = key($Message->getTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail03();
        $this->actual = key($Message->getReplyTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail01();
        $this->actual = key($Message->getBcc());

        // HTML??????????????????
        $this->assertEquals(1, count($Message->getChildren()));
    }

    public function testSendAdminCustomerConfirmMail()
    {
        $url = 'http://example.com/confirm';
        $this->mailService->sendAdminCustomerConfirmMail($this->Customer, $url);

        $mailCollector = $this->getMailCollector();
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        /** @var \Swift_Message $Message */
        $Message = $collectedMessages[0];

        $this->expected = $url;
        $this->verifyRegExp($Message, 'URL???'.$url.'?????????????????????');

        $this->expected = '['.$this->BaseInfo->getShopName().'] ????????????????????????';
        $this->actual = $Message->getSubject();
        $this->verify();

        $this->expected = $this->Customer->getEmail();
        $this->actual = key($Message->getTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail03();
        $this->actual = key($Message->getReplyTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail01();
        $this->actual = key($Message->getBcc());
        $this->verify();

        // HTML??????????????????
        $this->assertEquals(1, count($Message->getChildren()));
    }

    public function testSendAdminOrderMail()
    {
        $Order = $this->Order;
        $faker = $this->getFaker();
        $subject = $faker->sentence;
        $formData = [
            'mail_subject' => $subject,
            'tpl_data' => $faker->text,
        ];
        $this->mailService->sendAdminOrderMail($Order, $formData);

        $mailCollector = $this->getMailCollector();
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        /** @var \Swift_Message $Message */
        $Message = $collectedMessages[0];

        $this->expected = '['.$this->BaseInfo->getShopName().'] '.$subject;
        $this->actual = $Message->getSubject();
        $this->verify();

        $this->expected = $Order->getEmail();
        $this->actual = key($Message->getTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail03();
        $this->actual = key($Message->getReplyTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail01();
        $this->actual = key($Message->getBcc());
        $this->verify();
    }

    public function testSendPasswordResetNotificationMail()
    {
        $url = 'http://example.com/reset';
        $this->mailService->sendPasswordResetNotificationMail($this->Customer, $url);

        $mailCollector = $this->getMailCollector();
        $this->assertLessThanOrEqual(1, $mailCollector->getMessageCount(), 'Bcc???????????????????????????');

        $collectedMessages = $mailCollector->getMessages();
        /** @var \Swift_Message $Message */
        $Message = $collectedMessages[0];

        $this->expected = $url;
        $this->verifyRegExp($Message, 'URL???'.$url.'?????????????????????');

        $this->expected = '['.$this->BaseInfo->getShopName().'] ?????????????????????????????????';
        $this->actual = $Message->getSubject();
        $this->verify();

        $this->expected = $this->Customer->getEmail();
        $this->actual = key($Message->getTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail03();
        $this->actual = key($Message->getReplyTo());
        $this->verify();

        // HTML??????????????????
        $this->assertEquals(0, count($Message->getChildren()));
    }

    public function testSendPasswordResetCompleteMail()
    {
        $faker = $this->getFaker();
        $password = $faker->password;
        $this->mailService->sendPasswordResetCompleteMail($this->Customer, $password);

        $mailCollector = $this->getMailCollector();
        $this->assertLessThanOrEqual(1, $mailCollector->getMessageCount(), 'Bcc???????????????????????????');

        $collectedMessages = $mailCollector->getMessages();
        /** @var \Swift_Message $Message */
        $Message = $collectedMessages[0];

        $this->expected = '['.$this->BaseInfo->getShopName().'] ????????????????????????????????????';
        $this->actual = $Message->getSubject();
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail03();
        $this->actual = key($Message->getReplyTo());
        $this->verify();

        $this->expected = $this->BaseInfo->getEmail01();
        $this->actual = key($Message->getBcc());
        $this->verify();

        // HTML??????????????????
        $this->assertEquals(0, count($Message->getChildren()));
    }

    public function testConvertMessageISO()
    {
        // TODO  https://github.com/EC-CUBE/ec-cube/issues/2402#issuecomment-362487022
        $this->markTestSkipped('????????????????????????????????????');
        $config = $this->app['config'];
        $config['mail']['charset_iso_2022_jp'] = true;
        $this->app['config'] = $config;

        $this->app->initMailer();

        $Order = $this->Order;
        // ????????????iso-2022-jp???message
        $message = $this->app['eccube.service.mail']->sendOrderMail($Order);

        $this->expected = mb_strtolower($message->getCharset());
        $this->actual = 'iso-2022-jp';
        $this->verify();

        $this->expected = $message->getBody();

        // ??????????????????iso-2022-jp??????UTF-8?????????????????????????????????
        // MailUtil::convertMessage($this->app, $message);
        // $this->actual = $message->getBody();
        // $this->assertNotEquals($this->expected, $this->actual);

        // ??????????????????UTF-8??????iso-2022-jp?????????????????????????????????
        MailUtil::setParameterForCharset($this->app, $message);
        $this->actual = $message->getBody();
        $this->assertEquals($this->expected, $this->actual);

        $config = $this->app['config'];
        $config['mail']['charset_iso_2022_jp'] = false;
        $this->app['config'] = $config;
    }

    public function testConvertMessageUTF()
    {
        // TODO  https://github.com/EC-CUBE/ec-cube/issues/2402#issuecomment-362487022
        $this->markTestSkipped('????????????????????????????????????');

        $config = $this->app['config'];
        $config['mail']['charset_iso_2022_jp'] = false;
        $this->app['config'] = $config;

        $this->app->initMailer();

        $Order = $this->Order;
        // ????????????UTF???message
        $message = $this->app['eccube.service.mail']->sendOrderMail($Order);

        $this->expected = mb_strtolower($message->getCharset());
        $this->actual = 'utf-8';
        $this->verify();

        $this->expected = $message->getBody();

        // ??????????????????
        MailUtil::convertMessage($this->app, $message);
        $this->actual = $message->getBody();
        $this->verify();

        // ??????????????????
        MailUtil::setParameterForCharset($this->app, $message);
        $this->actual = $message->getBody();
        $this->verify();
    }

    /**
     * @throws \Twig_Error
     */
    public function testSendShippingNotifyMail()
    {
        $this->markTestSkipped('????????????????????????????????????');
        $Order = $this->Order;
        /** @var Shipping $Shipping */
        $Shipping = $Order->getShippings()->first();

        $this->mailService->sendShippingNotifyMail($Shipping);
        $this->entityManager->flush();

        $messages = $this->getMailCollector()->getMessages();
        self::assertEquals(1, count($messages));

        /** @var \Swift_Message $message */
        $message = $messages[0];
        self::assertEquals([$Order->getEmail() => 0], $message->getTo(), '????????????????????????????????????????????????');

        /** @var MailHistoryRepository $mailHistoryRepository */
        $mailHistoryRepository = $this->container->get(MailHistoryRepository::class);
        $histories = $mailHistoryRepository->findBy(['Order' => $Order]);
        self::assertEquals(1, count($histories), '?????????????????????????????????????????????');

        // HTML??????????????????
        $this->assertEquals(1, count($Message->getChildren()));
    }

    protected function verifyRegExp($Message, $errorMessage = null)
    {
        $this->assertRegExp('/'.preg_quote($this->expected, '/').'/', $Message->getBody(), $errorMessage);
    }
}
