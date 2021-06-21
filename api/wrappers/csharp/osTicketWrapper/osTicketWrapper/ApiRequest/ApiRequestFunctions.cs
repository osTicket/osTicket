using System.Net;
using RestSharp;

namespace osTicketWrapper.ApiRequest
{
    public static class ApiRequestFunctions
    {
        /// <summary>
        /// Validates a StatusCode.
        /// </summary>
        public static bool IsValidStatusCode(this RequestResult RequestResult)
        {
            return IsValidStatusCode(RequestResult.StatusCode, RequestResult.Method);
        }

        /// <summary>
        /// Validates a StatusCode.
        /// </summary>
        public static bool IsValidStatusCode(this HttpStatusCode Code, Method Method)
        {
            if (Code == HttpStatusCode.Accepted || Code == HttpStatusCode.OK || Code == HttpStatusCode.NoContent)
            {
                return true;
            }

            if (Method == Method.POST && Code == HttpStatusCode.Created)
            {
                return true;
            }

            if (Method == Method.PUT && Code == HttpStatusCode.NoContent)
            {
                return true;
            }

            if (Method == Method.DELETE && Code == HttpStatusCode.NoContent)
            {
                return true;
            }

            return false;
        }
    }
}