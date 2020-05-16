<?php
/**
 * @author flavienb.com
 */

require(FRAMEWORK_PATH . '__lib/emogrifier.php');

final class __mail {

    static private $initialized = false;
    static private $view = null;
    static private $cc = array();
    static private $bcc = array();
    static private $replyTo = null;

    static public function init($viewPath=null,$data=array()) {
        self::$view = null;
        self::$cc = array();
        self::$bcc = array();
        self::$replyTo = null;

        if ($viewPath) {
            self::$view = new __view($viewPath);
            self::$view->setData($data);
        }
    }

    static public function data($data) {
        if (self::$view) {
            self::$view->setData($data);
        }
    }

    static public function cc($emails) {
        self::$cc = array_merge(self::$cc,(array)$emails);
    }

    static public function bcc($emails) {
        self::$bcc = array_merge(self::$bcc,(array)$emails);
    }

    static public function replyTo($email) {
        self::$replyTo = $email;
    }

    static public function send($from,$to,$subject,$fromName=null,$body=null) {
        if (!self::$initialized) {
            require(FRAMEWORK_PATH . '__lib/phpmailer.php');
            require(FRAMEWORK_PATH . '__lib/Html2Text.php');
            self::$initialized = true;
        }

        $mail = new PHPMailer(true);
        $mail->IsHTML(true);
        $mail->IsMail();
        $mail->From = $from;
        $mail->FromName = $from;
        if ($fromName) {
            $mail->FromName = $fromName;
        }
        $mail->Subject = $subject;


        if (is_array($to)) {
            $mail->AddAddress(array_shift($to));
            self::bcc($to);
        } else {
            if (__config::get('MAIL_SMTP')) {
                $mail->IsSMTP();
                $mail->Host = __config::get('MAIL_SMTP_HOST');
                $mail->Port = __config::get('MAIL_SMTP_PORT');
                if (__config::get('MAIL_SMTP_USERNAME')) {
                    $mail->SMTPAuth = true;
                    $mail->Username = __config::get('MAIL_SMTP_USERNAME');
                    $mail->Password = __config::get('MAIL_SMTP_PASSWORD');
                }
            }
            $mail->AddAddress($to);
        }


        foreach((array)self::$bcc as $email) {
            $mail->addBCC($email);
        }

        foreach((array)self::$cc as $email) {
            $mail->addCC($email);
        }

        if (self::$replyTo) {
            $mail->addReplyTo(self::$replyTo);
        }

        if ($body) {
            $mail->Body = $body;
        }
        elseif(self::$view) {
            $html = self::$view->getDisplay();
            $emogrifier = new \Pelago\Emogrifier($html);

            $mail->Body = $emogrifier->emogrify();

            $html2Text = new \Html2Text\Html2Text($html);
            $mail->AltBody = $html2Text->getText();
        }
        else {
            $mail->Body = '';
        }

        $mail->Send();
    }

}
