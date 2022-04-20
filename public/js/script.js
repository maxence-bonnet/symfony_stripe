// Gérer l'échec du paiement de l'abonnement
// Client et serveur
// Si la valeur du paramètre subscription.latest_invoice.payment_intent.status est requires_payment_method, 
// cela signifie que la carte bancaire a été traitée lorsque le client a fourni ses informations de carte, 
// mais que le paiement a échoué par la suite. Dans un scénario de production, cela peut par exemple se produire 
// si la carte d’un client a été volée ou annulée après la mise en place de l’abonnement. L’exemple s’appuie sur 
// l’objet de réponse de l’API pour vous permettre de tester la gestion des erreurs. En mode production, 
// vous devez surveiller l’événement du webhook invoice.payment_failed pour tous les paiements ultérieurs 
// à la réussite du paiement initial.

// Détectez l’erreur, signalez au client que sa carte a été refusée et renvoyez-le au formulaire de paiement 
// pour qu’il essaie avec une autre carte.
function handleRequiresPaymentMethod({
    subscription,
    paymentMethodId,
    priceId,
  }) {
    if (subscription.status === 'active') {
      // subscription is active, no customer actions required.
      return { subscription, priceId, paymentMethodId };
    } else if (
      subscription.latest_invoice.payment_intent.status ===
      'requires_payment_method'
    ) {
      // Using localStorage to manage the state of the retry here,
      // feel free to replace with what you prefer.
      // Store the latest invoice ID and status.
      localStorage.setItem('latestInvoiceId', subscription.latest_invoice.id);
      localStorage.setItem(
        'latestInvoicePaymentIntentStatus',
        subscription.latest_invoice.payment_intent.status
      );
      throw { error: { message: 'Your card was declined.' } };
    } else {
      return { subscription, priceId, paymentMethodId };
    }
  }

// Sur le front-end, définissez la fonction pour joindre la nouvelle carte au client, puis mettez à jour les
//  paramètres de facturation. Transmettez le client, le nouveau moyen de paiement, la facture et les ID 
// de tarif à un endpoint du back-end.
function retryInvoiceWithNewPaymentMethod({
    customerId,
    paymentMethodId,
    invoiceId,
    priceId
  }) {
    return (
      fetch('/retry-invoice', {
        method: 'post',
        headers: {
          'Content-type': 'application/json',
        },
        body: JSON.stringify({
          customerId: customerId,
          paymentMethodId: paymentMethodId,
          invoiceId: invoiceId,
        }),
      })
        .then((response) => {
          return response.json();
        })
        // If the card is declined, display an error to the user.
        .then((result) => {
          if (result.error) {
            // The card had an error when trying to attach it to a customer.
            throw result;
          }
          return result;
        })
        // Normalize the result to contain the object returned by Stripe.
        // Add the additional details we need.
        .then((result) => {
          return {
            // Use the Stripe 'object' property on the
            // returned result to understand what object is returned.
            invoice: result,
            paymentMethodId: paymentMethodId,
            priceId: priceId,
            isRetry: true,
          };
        })
        // Some payment methods require a customer to be on session
        // to complete the payment process. Check the status of the
        // payment intent to handle these actions.
        .then(handlePaymentThatRequiresCustomerAction)
        // No more actions required. Provision your service for the user.
        .then(onSubscriptionComplete)
        .catch((error) => {
          // An error has happened. Display the failure to the user here.
          // We utilize the HTML element we created.
          displayError(error);
        })
    );
// Sur le back-end, définissez le endpoint que votre front-end appellera. Le code met à 
// jour le nouveau moyen de paiement pour le client et l’affecte comme nouveau moyen de 
// paiement par défaut pour les factures liées à l’abonnement.
// https://stripe.com/docs/billing/subscriptions/fixed-price#manage-subscription-payment-failure