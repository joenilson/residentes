<?php
/*
 * Copyright (C) 2021 Joe Nilson <joenilson@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';

require 'plugins/residentes/extras/vendor/autoload.php';

use \Mailjet\Resources;

class ResidentesEnviarMail
{
    public $log;
    public $rc;
    public $emailConfig;

    public function __construct()
    {
        $this->log = new fs_core_log();
        $this->rc = new residentes_controller();
        $remcfg = new residentes_email_config();
        $this->emailConfig = $remcfg->currentConfig();
    }

    public function internalEmailTest()
    {

    }

    public function internalEmail(&$companyInformation, &$invoice, &$customer, $user, $archivo, $subject)
    {
        $mail = $companyInformation->new_mail();
        $mail->FromName = (is_object($user)) ? $user->get_agente_fullname() : $companyInformation->nombre;
        $email = $customer->email;
        $this->log->new_message('Enviando factura a: '.$email);
        $mail->addAddress($email, $customer->nombre);
        $mail->Subject = $subject;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n",html_entity_decode($this->emailConfig->emailsubject)));
        $this->emailAdditionalInfo($mail, $customer);
        $mail->msgHTML(html_entity_decode($this->emailConfig->emailsubject));
        $mail->isHTML(true);
        $mail->addAttachment('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
        $this->emailAdditionalAttachments($mail);
        if ($companyInformation->mail_connect($mail) && $mail->send()) {
            $invoice->femail = \date('d-m-Y');;
            $invoice->save();
            $companyInformation->save_mail($mail);
        } else {
            $this->log->new_error("Error al enviar el email: " . $mail->ErrorInfo);
        }
        unlink('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
    }

    public function sendgridEmailTest()
    {
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom($this->emailConfig->apisenderemail, $this->emailConfig->apisendername);
        $email->setSubject("Sending with SendGrid is Fun");
        $email->addTo($this->emailConfig->apisenderemail, $this->emailConfig->apisendername);
        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html", "<strong>and easy to do anywhere, even with PHP</strong>"
        );
        $sendgrid = new \SendGrid($this->emailConfig->apikey);
        try {
            $response = $sendgrid->send($email);
            return $response->statusCode();
        } catch (Exception $e) {
            return "ERROR";
        }
    }

    public function sendgridEmail(&$companyInformation, &$invoice, &$customer, $user, $archivo, $subject)
    {
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom($this->emailConfig->apisenderemail, $this->emailConfig->apisendername);
        $email->setSubject($subject);
        $email->addTo($customer->email, $customer->nombre);
        $email->addContent("text/plain", strip_tags(str_replace("<br>", "\n",html_entity_decode($this->emailConfig->emailsubject))),);
        $email->addContent("text/html", html_entity_decode($this->emailConfig->emailsubject));
        $file_encoded = base64_encode(file_get_contents('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo));
        $email->addAttachment(
            $file_encoded,
            "application/pdf",
            $invoice->numero2.".pdf",
            "attachment"
        );

        $sendgrid = new \SendGrid($this->emailConfig->apikey);
        try {
            $response = $sendgrid->send($email);
            return $response->statusCode();
        } catch (Exception $e) {
            return "ERROR";
        }
    }

    public function mailjetEmailTest()
    {
        $mj = new \Mailjet\Client($this->emailConfig->apikey,$this->emailConfig->apisecret,true,['version' => 'v3.1']);
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $this->emailConfig->apisenderemail,
                        'Name' => $this->emailConfig->apisendername
                    ],
                    'To' => [
                        [
                            'Email' => $this->emailConfig->apisenderemail,
                            'Name' => $this->emailConfig->apisendername
                        ]
                    ],
                    'Subject' => "Greetings from Mailjet.",
                    'TextPart' => "My first Mailjet email",
                    'HTMLPart' => "<h3>Dear passenger 1, welcome to <a href='https://www.mailjet.com/'>Mailjet</a>!</h3><br />May the delivery force be with you!",
                    'CustomID' => "AppGettingStartedTest"
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);
        return ($response->success() === 200) ? $response->success() : "ERROR";
    }

    public function mailjetEmail(&$companyInformation, &$invoice, &$customer, $user, $archivo, $subject)
    {
        $mj = new \Mailjet\Client($this->emailConfig->apikey,$this->emailConfig->apisecret,true,['version' => 'v3.1']);
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $this->emailConfig->apisenderemail,
                        'Name' => $this->emailConfig->apisendername
                    ],
                    'To' => [
                        [
                            'Email' =>$customer->email,
                            'Name' => $customer->nombre
                        ]
                    ],
                    'Subject' => $subject,
                    'TextPart' => strip_tags(str_replace("<br>", "\n",html_entity_decode($this->emailConfig->emailsubject))),
                    'HTMLPart' => html_entity_decode($this->emailConfig->emailsubject),
                    'Attachments' => [
                        [
                            'ContentType' => 'application/pdf',
                            'Filename' => $invoice->numero2.'.pdf',
                            'Base64Content' => base64_encode(
                                file_get_contents('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo)
                            )
                        ]
                    ]
                ]
            ]
        ];

        $response = $mj->post(Resources::$Email, ['body' => $body]);
        return ($response->success() === 200) ? $response->success() : "ERROR";
    }

    public function invoiceEmail(&$companyInformation, &$invoice, &$customer, $user, $archivo)
    {
        if (file_exists('tmp/'.FS_TMP_NAME.'enviar/'.$archivo)) {
            $subject = fs_fix_html($companyInformation->nombre) .
            ' - Su Factura ' . $invoice->codigo . ' ' . $invoice->numero2;
            switch($this->emailConfig->tiposervicio) {
                case "sendgrid":
                    $this->sendgridEmail($companyInformation, $invoice, $customer, $user, $archivo, $subject);
                    break;
                case "mailjet":
                    $this->mailjetEmail($companyInformation, $invoice, $customer, $user, $archivo, $subject);
                    break;
                case "interno":default:
                    $this->internalEmail($companyInformation, $invoice, $customer, $user, $archivo, $subject);
                    break;
            }
//
//            $mail = $companyInformation->new_mail();
//            $mail->FromName = (is_object($user)) ? $user->get_agente_fullname() : $companyInformation->nombre;
//            $email = $customer->email;
//            $this->log->new_message('Enviando factura a: '.$email);
//            $mail->addAddress($email, $customer->nombre);
//            $elSubject = ' - Su Factura ' . $invoice->codigo . ' ' . $invoice->numero2;
//            $mail->Subject = fs_fix_html($companyInformation->nombre) . $elSubject;
//            $mail->AltBody = plantilla_email(
//                'factura',
//                $invoice->codigo . ' ' . $invoice->numero2,
//                $companyInformation->email_config['mail_firma']
//            );
//            $this->emailAdditionalInfo($mail, $customer);
//            $mail->msgHTML(nl2br($mail->AltBody));
//            $mail->isHTML(true);
//            $mail->addAttachment('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
//            $this->emailAdditionalAttachments($mail);
//            if ($companyInformation->mail_connect($mail) && $mail->send()) {
//                $invoice->femail = \date('d-m-Y');;
//                $invoice->save();
//                $companyInformation->save_mail($mail);
//            } else {
//                $this->log->new_error("Error al enviar el email: " . $mail->ErrorInfo);
//            }
            unlink('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
        } else {
            $this->log->new_error('Imposible generar el PDF.');
        }
    }

    public function emailAdditionalInfo(&$mail, $customer)
    {
        if (trim($this->rc->filter_request('email_copia')) !== '') {
            if ($this->rc->filter_request('cco') !== null) {
                $mail->addBCC(
                    trim($this->rc->filter_request('email_copia')),
                    $customer->nombre
                );
            } else {
                $mail->addCC(
                    trim($this->rc->filter_request('email_copia')),
                    $customer->nombre
                );
            }
        }
    }

    public function emailAdditionalAttachments(&$mail)
    {
        if (isset($_FILES['adjunto']) && is_uploaded_file($_FILES['adjunto']['tmp_name'])) {
            $mail->addAttachment($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
        }
    }

    public function accountStatusEmail(&$empresa, &$customer, $user, $archivo)
    {
        $mail = $empresa->new_mail();
        $mail->FromName = (is_object($user)) ? $user->get_agente_fullname() : $empresa->nombre;
        $email = (trim($this->rc->filter_request('email')) !== '')
            ? $this->rc->filter_request('email')
            : $customer->email;
        $this->log->new_message('Enviando Estado de cuenta a: '.$email);
        $mail->addAddress($email, $customer->nombre);
        $elSubject = ': Su Estado de cuenta al '. \date('d-m-Y');
        $mail->Subject = fs_fix_html($empresa->nombre) . $elSubject;
        $mail->AltBody = strip_tags($_POST['mensaje']);
        $this->emailAdditionalAttachments($mail);
        $mail->msgHTML(nl2br($mail->AltBody));
        $mail->isHTML(true);
        $mail->addAttachment('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
        $this->emailAdditionalAttachments($mail);
        if ($empresa->mail_connect($mail) && $mail->send()) {
            $empresa->save_mail($mail);
        } else {
            $this->log->new_error("Error al enviar el email: " . $mail->ErrorInfo);
        }
        unlink('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
    }
}