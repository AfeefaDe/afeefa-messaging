# afeefa-messaging
Service in the afeefa universe which is responsible for everything that is sending and receiving messages like mails or push notifications etc.

## Responsibilites

### Sending update requests
[triggered by cronjob]
- periodically ask owners to verify the data of their entries (projects, events etc.)
- owners receive an email, that offers them an easy way to check their data
    - entry data is ok > nothing to do
    - entry data is invalid > need to update
    - entry is obsolete > please delete

![User Journey for System](readme/user-journey-of-system-for-update-mails.jpg)

### Sending mails to edit entries
[triggered by user]
- at the request of an user (propably the owner) to edit an entry in the afeefa database, an email is send to the entry's owner
- the email offers an easy way to update the data
    - entry data is invalid > need to update
    - entry is obsolete > please delete

## Implementation
- use magic edit links? life time lasts for one interaction or for a certain time X? 

## Dependencies to other services
- needs information from `afeefa-backend-api` about outdated entries to send the emails
- needs end points for entry editing, which are handed out to the users (data ok, data update, data delete ...)
- where to keep the information, that some messaging happened?