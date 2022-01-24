##### An API project that connects to Facebook's Graph API to retrieve ad data.

## The problem

Most marketing teams use Facebook Ads to advertise on the still most used social media apps today: Facebook and Instagram. With the Facebook API, we can easily make automatic reports with other DataViz tools, such as PowerBI, Tableau, Metabase, etc.

This project focus on obtaining the data, connecting to the API, selecting our data from the Ad Insights section of the API, which provides the performance metrics of each campaigns, ad sets and ads respectively.
One problem is this API endpoint doesn't provide much data on the links used on the ad, and as such, an extra step is performed on the Ad Creatives endpoint, which stores each Facebook Ad Creative the team has used, and joins both data (ad metrics and creative URLs) based on the Ad ID.

## References and Tools Used

This API was built using PHP and the Laragon toolkit. It was also necessary to install Facebook's SDK with the composer module before building this code, which can be found on the links below.

The documentation for both these Facebook's Graph API can be found here:
1. [Ad Insights](https://developers.facebook.com/docs/marketing-api/insights/)
2. [Ad Creative](https://developers.facebook.com/docs/marketing-api/reference/ad-creative/)
3. [Facebook SDK](https://developers.facebook.com/docs/business-sdk/getting-started/?locale=pt_BR)
