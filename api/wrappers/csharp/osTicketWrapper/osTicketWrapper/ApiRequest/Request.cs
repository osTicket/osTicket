using RestSharp;
using System;
using System.Collections.Generic;

namespace osTicketWrapper.ApiRequest
{
    public class Request<TObject>
    {
        public Request(Method Method, String Function, string URL, TObject Object, Dictionary<string, string> HeaderParameters)
        {
            this.HeaderParameters = HeaderParameters;
            this.Function = Function;
            this.Object = Object;
            this.Method = Method;
            this.URL = URL;
        }

        public Request()
        {
        }

        public Dictionary<string, string> HeaderParameters { get; set; }

        public String Function { get; set; }
        public String URL { get; set; }

        public Method Method { get; set; }

        public TObject Object { get; set; }
    }
}