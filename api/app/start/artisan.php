<?php

/*
|--------------------------------------------------------------------------
| Register The Artisan Commands
|--------------------------------------------------------------------------
|
| Each available Artisan command must be registered with the console so
| that it is available to be called. We'll register every command so
| the console gets access to each of the command object instances.
|
*/
Artisan::add(new NotifyCommand);
Artisan::add(new NotifyReplyCommand);
Artisan::add(new NotifySendTicketCommand);
Artisan::add(new NotifySendTicketReplyCommand);
Artisan::add(new NotifyAssignCommand);
Artisan::add(new CronjobInsertSms);
Artisan::add(new CronjobSms);
Artisan::add(new NotifyForgotPasswordCommand);
Artisan::add(new NotifySendForgotPasswordCommand);
Artisan::add(new NotifyTicketAssignUserCommand);
Artisan::add(new NotifySendTicketAssignUserCommand);
Artisan::add(new CronjobCashIn);
Artisan::add(new CronjobUpdateFeeze);
Artisan::add(new NotifyWhenPickupSuccessCommand);
Artisan::add(new NotifySendPickupSuccessCommand);
Artisan::add(new NotifyChangeEmailNlCommand);
Artisan::add(new NotifySendChangeEmailNlCommand);
Artisan::add(new NotifyEmailVerifyCommand);
Artisan::add(new NotifySendEmailVerifyCommand);
Artisan::add(new NotifyCreateOrderCommand);
Artisan::add(new NotifySendCreateOrderCommand);
Artisan::add(new NotifyDeliveryFailCommand);
Artisan::add(new NotifySendDeliveryFailCommand);
Artisan::add(new NotifyReturnOrderCommand);
Artisan::add(new NotifySendReturnOrderCommand);
Artisan::add(new NotifyOrderOverWeightCommand);
Artisan::add(new NotifySendOrderOverWeightCommand);

// Accept Lading to Courier
Artisan::add(new AcceptViettelPostHN);
Artisan::add(new AcceptViettelPostHCM);
Artisan::add(new AcceptOtherCourier);
Artisan::add(new AcceptViettelPostOther);
Artisan::add(new JourneyCourier);
Artisan::add(new JourneyCourierCrawl);

//migrate live chat
Artisan::add(new CronjobLiveChat);

//Sync report data
Artisan::add(new CronjobSyncReportData);
Artisan::add(new CronjobImportReportData);