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

class ResidentesEnviarMail
{
    public $log;
    public $rc;
    public function __construct()
    {
        $this->log = new fs_core_log();
        $this->rc = new residentes_controller();
    }

    public function invoiceEmail(&$companyInformation, &$invoice, &$customer, $user, $archivo)
    {
        if (file_exists('tmp/'.FS_TMP_NAME.'enviar/'.$archivo)) {
            $mail = $companyInformation->new_mail();
            $mail->FromName = (is_object($user)) ? $user->get_agente_fullname() : $companyInformation->nombre;
            $email = $customer->email;
            $this->log->new_message('Enviando factura a: '.$email);
            $mail->addAddress($email, $customer->nombre);
            $elSubject = ' - Su Factura ' . $invoice->codigo . ' ' . $invoice->numero2;
            $mail->Subject = fs_fix_html($companyInformation->nombre) . $elSubject;
            $mail->AltBody = plantilla_email(
                'factura',
                $invoice->codigo . ' ' . $invoice->numero2,
                $companyInformation->email_config['mail_firma']
            );
            $this->emailAdditionalInfo($mail, $customer);
            $mail->msgHTML(nl2br($mail->AltBody));
            $mail->isHTML(true);
            $mail->addAttachment('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
            $this->emailAdditionalAttachments($mail);
            if ($companyInformation->mail_connect($mail) && $mail->send()) {
                $invoice->femail = \date('d-m-Y');;
                $invoice->save();
                $companyInformation->save_mail($mail);
            } else {
                $this->log->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            }
            unlink('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
        } else {
            $this->log->new_error_msg('Imposible generar el PDF.');
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
            $this->log->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
        }
        unlink('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
    }
}