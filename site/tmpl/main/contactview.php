<?php
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
?>

<div id="neukomtemplating-contactform">
    <form action="<?php echo Route::_(Uri::getInstance()->toString()); ?>" enctype="multipart/form-data" method="post" name="contactForm" id="contactForm" class="form-vertical form-validate">
        <?php
            $twigParams = [
                'data' => $item->data[$recordId],
            ];

            echo '<h2 class="neukomtemplating-contact-title">' . Text::_('COM_NEUKOMTEMPLATING_CONTACT_TITLE') . $twig->render('contact_display_name', array_merge($twigParams, $item->aliases)) . '</h2>';
        ?>

        <div class="neukomtemplating-contact-field">
            <label class="neukomtemplating-contact-label" for="sender-name"><?php echo Text::_('COM_NEUKOMTEMPLATING_CONTACT_SENDER_NAME') . '*'; ?></label>
            <input class="neukomtemplating-contact-input form-control" name="sender-name" id="sender-name" type="text" />
        </div>

        <div class="neukomtemplating-contact-field">
            <label class="neukomtemplating-contact-label" for="sender-email"><?php echo Text::_('COM_NEUKOMTEMPLATING_CONTACT_SENDER_EMAIL') . '*'; ?></label>
            <input class="neukomtemplating-contact-input form-control" name="sender-email" id="sender-email" type="text" />
        </div>

        <div class="neukomtemplating-contact-field">
            <label class="neukomtemplating-contact-label" for="message-subject"><?php echo Text::_('COM_NEUKOMTEMPLATING_CONTACT_MESSAGE_SUBJECT') . '*'; ?></label>
            <input class="neukomtemplating-contact-input form-control" name="message-subject" id="message-subject" type="text" />
        </div>

        <div class="neukomtemplating-contact-field">
            <label class="neukomtemplating-contact-label" for="message-body"><?php echo Text::_('COM_NEUKOMTEMPLATING_CONTACT_MESSAGE_BODY') . '*'; ?></label>
            <textarea class="neukomtemplating-contact-input form-control" name="message-body" id="message-body" rows="4" cols="50"></textarea>
        </div>

        <input type="hidden" id="formAction" name="formAction" value="message">
        <input type="hidden" id="recordId" name="recordId" value="<?php echo $recordId ?>">

        <div id="neukomtemplating-formbuttons">
            <button type="button" class="btn btn-primary" onclick="validateForm()"><?php echo Text::_('COM_NEUKOMTEMPLATING_SUBMIT') ?></button>
            <a type="button" class="btn btn-primary" id="backToListButton" href="<?php echo buildUrl($this, 'list'); ?>"><?php echo Text::_('COM_NEUKOMTEMPLATING_BACK'); ?></a>
        </div>
    </form>
</div>

<script>
    function validateEmail(email) {
        return email.match(
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        );
    }

    function validateForm() {
        var senderName = document.getElementById('sender-name');
        var senderEmail = document.getElementById('sender-email');
        var messageSubject = document.getElementById('message-subject');
        var messageBody = document.getElementById('message-body');

        var formValid = true;

        if (senderName.value.length <= 0 || senderName.value.length > 50) {
            senderName.classList.add('invalid');
            formValid = false;
        } else {
            senderName.classList.remove('invalid');
        }

        if (!validateEmail(senderEmail.value) || senderEmail.value.length > 100) {
            senderEmail.classList.add('invalid');
            formValid = false;
        } else {
            senderEmail.classList.remove('invalid');
        }

        if (messageSubject.value.length <= 0 || messageSubject.value.length > 50) {
            messageSubject.classList.add('invalid');
            formValid = false;
        } else {
            messageSubject.classList.remove('invalid');
        }

        if (messageBody.value.length <= 0 || messageBody.value.length > 500) {
            messageBody.classList.add('invalid');
            formValid = false;
        } else {
            messageBody.classList.remove('invalid');
        }

        if (formValid) {
            document.getElementById('contactForm').submit();
        }
    }
</script>