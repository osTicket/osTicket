using System.Text;
using RestSharp;
using System;
using System.Collections.Generic;
using osTicketWrapper.ApiRequest;
using osTicketWrapper.Models;

namespace osTicketWrapper
{
    /// <summary>
    /// Wrapper for the osTicket API.
    /// </summary>
    public static class osTicketWrapper
    {
        /// <summary>
        /// Opens a new ticket using the osTicket's API.
        /// </summary>
        /// <param name="osTicketEndpoint">Your osTicket Endpoint.</param>
        /// <param name="osTicketAPIKey">Your osTicket API Key.</param>
        /// <param name="osTicketIP">Your osTicket IP.</param>
        /// <param name="osTicketUser">The user responsible to assign the ticket to.</param>
        /// <param name="osTicketUserEmail">The user's email to assign the ticket to.</param>
        /// <param name="Subject">The subject of the ticket.</param>
        /// <param name="Body">The body of the ticket.</param>
        /// <returns>The newly opened ticket's ID.</returns>
        public static string OpenTicket(string osTicketEndpoint, string osTicketAPIKey, string osTicketIP, string osTicketUser, string osTicketUserEmail, string Subject, StringBuilder Body)
        {
            #region Create Model

            var TicketRequest = new TicketRequest()
            {
                message = Body.ToString(),
                email = osTicketUserEmail,
                name = osTicketUser,
                autorespond = true,
                subject = Subject,
                ip = osTicketIP,
                source = "API",
                alert = true,
            };

            #endregion Create Model

            #region Request

            var HeaderAuthentication = new Dictionary<string, string>()
            {
                { "x-api-key", osTicketAPIKey }
            };

            var Request = new Request<TicketRequest>(Method.POST, "/api/tickets.json", osTicketEndpoint, TicketRequest, HeaderAuthentication);
            var RequestResult = ApiRequest<TicketRequest>.Command(Request);

            #region Validate Result

            if (RequestResult != null && !String.IsNullOrEmpty(RequestResult.Content) && RequestResult.IsValidStatusCode())
            {
                return RequestResult.Content;
            }

            #endregion Validate Result

            throw new ApplicationException("Failed to create new ticket.");

            #endregion Request
        }
    }
}